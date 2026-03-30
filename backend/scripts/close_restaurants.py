#!/usr/bin/env python3
"""
Script per chiudere automaticamente tutti i ristoranti OPPLA in un periodo specifico
Utilizzabile per ferie/vacanze/chiusure massive
"""

from selenium import webdriver
from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC
from selenium.webdriver.chrome.options import Options
import csv
import time
import sys
import argparse
from datetime import datetime, timedelta
import os
import psycopg2
import json
import requests

# Configurazione
LOGIN_URL = "https://api.oppla.delivery/admin/login"
HOLIDAYS_URL_TEMPLATE = "https://api.oppla.delivery/restaurants/{restaurant_id}/holidays/create"
CSV_PATH = os.path.join(os.path.dirname(__file__), "restaurants.csv")

# Credenziali (da variabili d'ambiente o parametri)
EMAIL = os.getenv("OPPLA_ADMIN_EMAIL", "lorenzo.moschella@oppla.delivery")
PASSWORD = os.getenv("OPPLA_ADMIN_PASSWORD", r"e>1k5Z.w?(KL|U%4b$)(,\QNjDqN;Y(]")

# Database PostgreSQL OPPLA (fallback se CSV non esiste)
DB_CONFIG = {
    'host': 'dpg-cktqf7enfb1c73ehil50-a.frankfurt-postgres.render.com',
    'port': 5432,
    'database': 'postgres_s4yp',
    'user': 'postgres_s4yp_user',
    'password': 'GYmJHlJg8Jye5TYeOl9OPZvyjOLM186m'
}


def setup_driver(headless=True):
    """
    Configura e restituisce il driver Selenium
    """
    chrome_options = Options()
    chrome_options.binary_location = '/usr/bin/google-chrome'
    if headless:
        chrome_options.add_argument('--headless=new')
        chrome_options.add_argument('--disable-gpu')
    chrome_options.add_argument('--no-sandbox')
    chrome_options.add_argument('--disable-dev-shm-usage')
    chrome_options.add_argument('--disable-setuid-sandbox')
    chrome_options.add_argument('--remote-debugging-port=9222')
    chrome_options.add_argument('--disable-extensions')
    chrome_options.add_argument('--disable-software-rasterizer')
    chrome_options.add_argument('--start-maximized')
    chrome_options.add_argument('--window-size=1920,1080')
    chrome_options.add_argument('--disable-blink-features=AutomationControlled')
    chrome_options.add_argument('--lang=it-IT')
    # Imposta timezone Europa/Roma
    chrome_options.add_argument('--tz=Europe/Rome')
    chrome_options.add_experimental_option("excludeSwitches", ["enable-automation"])
    chrome_options.add_experimental_option('useAutomationExtension', False)
    chrome_options.add_experimental_option('prefs', {'intl.accept_languages': 'it-IT'})

    driver = webdriver.Chrome(options=chrome_options)
    return driver


def login_to_oppla(driver):
    """
    Esegue il login su OPPLA Admin Panel usando Selenium
    """
    print(f"🔐 Login a {LOGIN_URL}...")
    
    try:
        driver.get(LOGIN_URL)
        time.sleep(3)
        
        # Trova campo email
        email_field = None
        selectors_email = [
            (By.NAME, "email"),
            (By.ID, "email"),
            (By.CSS_SELECTOR, "input[type='email']"),
            (By.CSS_SELECTOR, "input[name='email']"),
            (By.XPATH, "//input[@type='email']"),
        ]
        
        for selector_type, selector_value in selectors_email:
            try:
                email_field = WebDriverWait(driver, 5).until(
                    EC.presence_of_element_located((selector_type, selector_value))
                )
                break
            except:
                continue
        
        if not email_field:
            raise Exception("Campo email non trovato")
        
        # Cerca campo password
        password_field = None
        selectors_password = [
            (By.NAME, "password"),
            (By.ID, "password"),
            (By.CSS_SELECTOR, "input[type='password']"),
            (By.CSS_SELECTOR, "input[name='password']"),
        ]
        
        for selector_type, selector_value in selectors_password:
            try:
                password_field = driver.find_element(selector_type, selector_value)
                break
            except:
                continue
        
        if not password_field:
            raise Exception("Campo password non trovato")
        
        # Compila i campi
        email_field.clear()
        email_field.send_keys(EMAIL)
        password_field.clear()
        password_field.send_keys(PASSWORD)
        
        # Trova e clicca il pulsante di submit
        submit_selectors = [
            (By.CSS_SELECTOR, "button[type='submit']"),
            (By.XPATH, "//button[@type='submit']"),
            (By.CSS_SELECTOR, "input[type='submit']"),
            (By.TAG_NAME, "button"),
        ]
        
        submit_button = None
        for selector_type, selector_value in submit_selectors:
            try:
                submit_button = driver.find_element(selector_type, selector_value)
                break
            except:
                continue
        
        if submit_button:
            submit_button.click()
        else:
            from selenium.webdriver.common.keys import Keys
            password_field.send_keys(Keys.RETURN)
        
        # Attendi il caricamento
        time.sleep(5)
        
        # Verifica login
        if "login" not in driver.current_url.lower():
            print("Login effettuato con successo!")
            return True
        else:
            raise Exception(f"Login fallito! URL: {driver.current_url}")
            
    except Exception as e:
        print(f"❌ Errore durante il login: {e}")
        if not os.getenv('CI'):  # Solo se non in CI/CD
            driver.save_screenshot("login_error.png")
        return False


def get_restaurants_from_db():
    """
    Recupera i ristoranti dal database PostgreSQL OPPLA
    Prende TUTTI i ristoranti attivi
    """
    restaurants = []

    print(f"\n🗄️  Connessione al database OPPLA...")
    print(f"   Host: {DB_CONFIG['host']}")
    print(f"   Database: {DB_CONFIG['database']}")

    try:
        # Connessione al database
        conn = psycopg2.connect(
            host=DB_CONFIG['host'],
            port=DB_CONFIG['port'],
            database=DB_CONFIG['database'],
            user=DB_CONFIG['user'],
            password=DB_CONFIG['password'],
            connect_timeout=10
        )

        cursor = conn.cursor()

        # Query per ottenere TUTTI i ristoranti
        query = """
            SELECT id, name
            FROM restaurants
            ORDER BY name
        """

        cursor.execute(query)
        rows = cursor.fetchall()

        for row in rows:
            restaurants.append({
                'id': row[0],
                'name': row[1],
            })

        cursor.close()
        conn.close()

        print(f"✅ Trovati {len(restaurants)} ristoranti attivi")
        return restaurants

    except psycopg2.Error as e:
        print(f"❌ Errore connessione database: {e}")
        sys.exit(1)
    except Exception as e:
        print(f"❌ Errore durante il recupero dei ristoranti: {e}")
        sys.exit(1)


def read_restaurants_csv():
    """
    Legge il CSV e restituisce TUTTI i ristoranti attivi
    """
    restaurants = []

    print(f"\n📄 Lettura CSV da {CSV_PATH}...")
    
    try:
        with open(CSV_PATH, 'r', encoding='utf-8') as csvfile:
            reader = csv.DictReader(csvfile)
            
            for row in reader:
                # Prendi TUTTI i ristoranti (rimuovi filtro has_deliveries_managed)
                if row.get('id'):
                    restaurants.append({
                        'id': row.get('id'),
                        'name': row.get('name', 'N/A'),
                        'is_active': row.get('is_active', 'true').lower() in ['true', '1', 'yes'],
                    })
        
        # Filtra solo ristoranti attivi
        active_restaurants = [r for r in restaurants if r['is_active']]
        
        print(f"Trovati {len(active_restaurants)} ristoranti attivi (su {len(restaurants)} totali)")
        return active_restaurants
        
    except FileNotFoundError:
        print(f"⚠️  File CSV non trovato: {CSV_PATH}")
        print(f"💡 Provo a leggere dal database PostgreSQL OPPLA...")
        return get_restaurants_from_db()
    except Exception as e:
        print(f"❌ Errore durante la lettura del CSV: {e}")
        print(f"💡 Provo a leggere dal database PostgreSQL OPPLA...")
        return get_restaurants_from_db()


def disable_managed_deliveries(driver, restaurant_id, restaurant_name):
    """
    Disabilita le managed deliveries per un ristorante specifico

    Args:
        driver: WebDriver Selenium
        restaurant_id: ID del ristorante
        restaurant_name: Nome del ristorante (per log)

    Returns:
        bool: True se riuscito, False altrimenti
    """
    edit_url = f"https://api.oppla.delivery/restaurants/{restaurant_id}/edit"

    try:
        driver.get(edit_url)
        time.sleep(2)

        # Cerca il checkbox has_deliveries_managed
        try:
            # Prova diversi selettori per trovare il checkbox
            checkbox_selectors = [
                (By.ID, "data.has_deliveries_managed"),
                (By.NAME, "has_deliveries_managed"),
                (By.CSS_SELECTOR, "input[type='checkbox'][name*='has_deliveries_managed']"),
                (By.XPATH, "//input[@type='checkbox' and contains(@name, 'has_deliveries_managed')]"),
            ]

            checkbox = None
            for selector_type, selector_value in checkbox_selectors:
                try:
                    checkbox = WebDriverWait(driver, 5).until(
                        EC.presence_of_element_located((selector_type, selector_value))
                    )
                    break
                except:
                    continue

            if not checkbox:
                return False  # Campo non trovato, potrebbe già essere disabilitato

            # Se il checkbox è checked, lo disabilitiamo
            if checkbox.is_selected():
                checkbox.click()
                time.sleep(1)

                # Trova e clicca il pulsante Save
                save_button = None
                save_selectors = [
                    (By.XPATH, "//button[contains(., 'Save') or contains(., 'Salva')]"),
                    (By.CSS_SELECTOR, "button[type='submit']"),
                    (By.XPATH, "//button[@type='submit']"),
                ]

                for selector_type, selector_value in save_selectors:
                    try:
                        save_button = driver.find_element(selector_type, selector_value)
                        break
                    except:
                        continue

                if save_button:
                    save_button.click()
                    time.sleep(2)
                    return True

            return True  # Già disabilitato

        except Exception as e:
            return False

    except Exception as e:
        return False


def check_holiday_exists(restaurant_id, start_date, end_date):
    """
    Verifica se esiste già una chiusura per questo ristorante nel periodo specificato

    Args:
        restaurant_id: ID del ristorante
        start_date: Data/ora inizio (formato: YYYY-MM-DDTHH:MM)
        end_date: Data/ora fine (formato: YYYY-MM-DDTHH:MM)

    Returns:
        bool: True se esiste già una chiusura, False altrimenti
    """
    try:
        conn = psycopg2.connect(
            host=DB_CONFIG['host'],
            port=DB_CONFIG['port'],
            database=DB_CONFIG['database'],
            user=DB_CONFIG['user'],
            password=DB_CONFIG['password'],
            connect_timeout=5
        )

        cursor = conn.cursor()

        # Converti le date in oggetti datetime per il confronto
        # Sottrai 1 ora per compensare UTC+1 (le date nel DB sono in UTC)
        start_dt = datetime.strptime(start_date, "%Y-%m-%dT%H:%M") - timedelta(hours=1)
        end_dt = datetime.strptime(end_date, "%Y-%m-%dT%H:%M") - timedelta(hours=1)

        # Cerca chiusure sovrapposte
        query = """
            SELECT COUNT(*) FROM holidays
            WHERE restaurant_id = %s
            AND (
                (start <= %s AND "end" >= %s)  -- Copre tutto il periodo
                OR (start >= %s AND start <= %s)  -- Inizia nel periodo
                OR ("end" >= %s AND "end" <= %s)  -- Finisce nel periodo
            )
        """

        cursor.execute(query, (
            restaurant_id,
            start_dt, end_dt,
            start_dt, end_dt,
            start_dt, end_dt
        ))

        count = cursor.fetchone()[0]

        cursor.close()
        conn.close()

        return count > 0

    except Exception as e:
        # In caso di errore, assumiamo che non esista (per non bloccare l'operazione)
        print(f"⚠️ Errore verifica chiusura esistente: {e}")
        return False


def get_holiday_id_from_db(restaurant_id, start_date, end_date):
    """
    Recupera l'ID della chiusura appena creata dal database

    Args:
        restaurant_id: ID del ristorante
        start_date: Data/ora inizio (formato: YYYY-MM-DDTHH:MM)
        end_date: Data/ora fine (formato: YYYY-MM-DDTHH:MM)

    Returns:
        str: UUID della chiusura o None
    """
    try:
        conn = psycopg2.connect(
            host=DB_CONFIG['host'],
            port=DB_CONFIG['port'],
            database=DB_CONFIG['database'],
            user=DB_CONFIG['user'],
            password=DB_CONFIG['password'],
            connect_timeout=5
        )

        cursor = conn.cursor()

        # Converti le date sottraendo 1 ora (UTC+1 -> UTC)
        start_dt = datetime.strptime(start_date, "%Y-%m-%dT%H:%M") - timedelta(hours=1)
        end_dt = datetime.strptime(end_date, "%Y-%m-%dT%H:%M") - timedelta(hours=1)

        # Cerca la chiusura appena creata
        query = """
            SELECT id FROM holidays
            WHERE restaurant_id = %s
            AND start = %s
            AND "end" = %s
            ORDER BY created_at DESC
            LIMIT 1
        """

        cursor.execute(query, (restaurant_id, start_dt, end_dt))
        result = cursor.fetchone()

        cursor.close()
        conn.close()

        return str(result[0]) if result else None

    except Exception as e:
        print(f"⚠️ Errore recupero holiday_id: {e}")
        return None


def save_holiday_mapping(batch_id, holiday_id, restaurant_id, restaurant_name, api_url):
    """
    Salva il mapping batch_id -> holiday_id nel database OneManager

    Args:
        batch_id: ID del batch
        holiday_id: UUID della chiusura creata
        restaurant_id: UUID del ristorante
        restaurant_name: Nome del ristorante
        api_url: URL dell'API OneManager

    Returns:
        bool: True se riuscito
    """
    if not api_url or not batch_id or not holiday_id:
        return False

    try:
        response = requests.post(
            f"{api_url}/api/restaurants/save-holiday-mapping",
            json={
                'batch_id': batch_id,
                'oppla_holiday_id': holiday_id,
                'oppla_restaurant_id': restaurant_id,
                'restaurant_name': restaurant_name,
            },
            timeout=10
        )
        return response.status_code == 200
    except Exception as e:
        # Non blocchiamo l'operazione se il salvataggio del mapping fallisce
        return False


def create_holiday(driver, restaurant_id, restaurant_name, start_date, end_date, reason="Chiusura programmata", batch_id=None, api_url=None):
    """
    Crea una festività/chiusura per un ristorante specifico

    Args:
        driver: WebDriver Selenium
        restaurant_id: ID del ristorante
        restaurant_name: Nome del ristorante (per log)
        start_date: Data/ora inizio chiusura (formato: YYYY-MM-DDTHH:MM)
        end_date: Data/ora fine chiusura (formato: YYYY-MM-DDTHH:MM)
        reason: Motivazione chiusura (default: "Chiusura programmata")
        batch_id: ID del batch per tracking (opzionale)
        api_url: URL dell'API OneManager per salvare i mapping (opzionale)

    Returns:
        str: UUID della chiusura creata, None se fallito o già esistente
    """
    from selenium.webdriver.common.keys import Keys

    url = HOLIDAYS_URL_TEMPLATE.format(restaurant_id=restaurant_id)

    print(f"  🏪 {restaurant_name[:40]:<40} (ID: {restaurant_id})... ", end="", flush=True)

    # Verifica se esiste già una chiusura per questo periodo
    if check_holiday_exists(restaurant_id, start_date, end_date):
        print("⏭️  (chiusura già presente)")
        return None

    # Prima disabilita le managed deliveries
    disable_managed_deliveries(driver, restaurant_id, restaurant_name)

    try:
        driver.get(url)
        time.sleep(2)

        try:
            # Trova i campi datetime-local per start e end
            start_field = WebDriverWait(driver, 10).until(
                EC.presence_of_element_located((By.ID, "data.start"))
            )
            end_field = driver.find_element(By.ID, "data.end")

            # Usa JavaScript per settare i valori direttamente (no compensazione timezone)
            driver.execute_script("""
                arguments[0].value = arguments[1];
                arguments[0].dispatchEvent(new Event('input', { bubbles: true }));
                arguments[0].dispatchEvent(new Event('change', { bubbles: true }));
            """, start_field, start_date)

            driver.execute_script("""
                arguments[0].value = arguments[1];
                arguments[0].dispatchEvent(new Event('input', { bubbles: true }));
                arguments[0].dispatchEvent(new Event('change', { bubbles: true }));
            """, end_field, end_date)

            time.sleep(1)

            # Cerca campo "reason" se esiste
            try:
                reason_field = driver.find_element(By.ID, "data.reason")
                reason_field.clear()
                reason_field.send_keys(reason)
            except:
                pass  # Campo reason opzionale

            # Trova e clicca il pulsante "Create"
            create_button = None
            selectors_create = [
                (By.XPATH, "//button[contains(., 'Create') and not(contains(., 'another'))]"),
                (By.XPATH, "//button[@type='submit']"),
                (By.CSS_SELECTOR, "button[type='submit']"),
                (By.XPATH, "//button[.//span[text()='Create']]"),
            ]

            for selector_type, selector_value in selectors_create:
                try:
                    create_button = driver.find_element(selector_type, selector_value)
                    break
                except:
                    continue

            if not create_button:
                raise Exception("Pulsante 'Create' non trovato")

            create_button.click()
            time.sleep(2)

            # Verifica se siamo stati reindirizzati (successo)
            if "/holidays/create" not in driver.current_url:
                print("✓ ", end="", flush=True)

                # Recupera l'ID della chiusura dal database
                holiday_id = get_holiday_id_from_db(restaurant_id, start_date, end_date)

                if holiday_id and batch_id and api_url:
                    # Salva il mapping nel database OneManager
                    if save_holiday_mapping(batch_id, holiday_id, restaurant_id, restaurant_name, api_url):
                        print("✓")
                    else:
                        print("")
                else:
                    print("")

                return holiday_id
            else:
                print("⚠️")
                return None

        except Exception as field_error:
            print(f"❌ {str(field_error)[:50]}")
            return None

    except Exception as e:
        print(f"❌ {str(e)[:50]}")
        return None


def validate_datetime_format(date_str):
    """
    Valida che la stringa sia in formato YYYY-MM-DDTHH:MM
    """
    try:
        datetime.strptime(date_str, '%Y-%m-%dT%H:%M')
        return True
    except ValueError:
        return False


def main():
    """
    Funzione principale
    """
    parser = argparse.ArgumentParser(
        description='Chiusura massiva ristoranti OPPLA per periodo specifico'
    )
    parser.add_argument('--start', required=True,
                       help='Data/ora inizio chiusura (formato: YYYY-MM-DDTHH:MM, es: 2026-08-01T18:00)')
    parser.add_argument('--end', required=True,
                       help='Data/ora fine chiusura (formato: YYYY-MM-DDTHH:MM, es: 2026-08-31T11:00)')
    parser.add_argument('--reason', default='Chiusura programmata',
                       help='Motivazione chiusura (default: "Chiusura programmata")')
    parser.add_argument('--csv', default=None,
                       help=f'Path al file CSV con i ristoranti')
    parser.add_argument('--headless', action='store_true', default=True,
                       help='Esegui in modalità headless (default: True)')
    parser.add_argument('--visible', action='store_true',
                       help='Mostra il browser durante l\'esecuzione (disabilita headless)')
    parser.add_argument('--batch-id', default=None,
                       help='ID del batch per tracking (opzionale)')
    parser.add_argument('--api-url', default=None,
                       help='URL API OneManager per salvare i mapping (opzionale)')

    args = parser.parse_args()

    # Usa CSV_PATH come default se --csv non è specificato
    if args.csv is None:
        args.csv = CSV_PATH
    
    # Validazione date
    if not validate_datetime_format(args.start):
        print(f"❌ Formato data inizio non valido: {args.start}")
        print("   Usa formato: YYYY-MM-DDTHH:MM (es: 2026-08-01T18:00)")
        sys.exit(1)
    
    if not validate_datetime_format(args.end):
        print(f"❌ Formato data fine non valido: {args.end}")
        print("   Usa formato: YYYY-MM-DDTHH:MM (es: 2026-08-31T11:00)")
        sys.exit(1)
    
    # Verifica che end sia dopo start
    start_dt = datetime.strptime(args.start, '%Y-%m-%dT%H:%M')
    end_dt = datetime.strptime(args.end, '%Y-%m-%dT%H:%M')
    
    if end_dt <= start_dt:
        print("❌ La data di fine deve essere successiva alla data di inizio!")
        sys.exit(1)
    
    headless = args.headless and not args.visible

    # Imposta timezone Europa/Roma
    os.environ['TZ'] = 'Europe/Rome'
    time.tzset() if hasattr(time, 'tzset') else None

    print("=" * 80)
    print("🏖️  CHIUSURA MASSIVA RISTORANTI OPPLA")
    print("=" * 80)
    print(f"📅 Periodo: {args.start} → {args.end}")
    print(f"📝 Motivazione: {args.reason}")
    print(f"🖥️  Modalità: {'Headless' if headless else 'Visible'}")
    print(f"📁 CSV: {args.csv}")
    print("=" * 80)

    # 1. Setup Selenium
    driver = setup_driver(headless=headless)
    
    try:
        # 2. Login
        if not login_to_oppla(driver):
            print("❌ Login fallito, impossibile continuare")
            return 1
        
        # 3. Leggi i ristoranti dal CSV
        restaurants = read_restaurants_csv()
        
        if not restaurants:
            print("\n⚠️  Nessun ristorante trovato nel CSV")
            return 1
        
        # 4. Conferma utente (solo se non headless e non in CI)
        if not headless and not os.getenv('CI'):
            print(f"\n⚠️  Stai per chiudere {len(restaurants)} ristoranti!")
            confirm = input("Confermi? (scrivi 'SI' per procedere): ")
            if confirm.strip().upper() != 'SI':
                print("❌ Operazione annullata dall'utente")
                return 0
        
        # 5. Crea le chiusure
        print(f"\n🎯 Creazione chiusure per {len(restaurants)} ristoranti...\n")

        success_count = 0
        failed_count = 0
        skipped_count = 0
        failed_restaurants = []
        created_holidays = []

        for i, restaurant in enumerate(restaurants, 1):
            print(f"[{i}/{len(restaurants)}] ", end="")
            holiday_id = create_holiday(
                driver,
                restaurant['id'],
                restaurant['name'],
                args.start,
                args.end,
                args.reason,
                batch_id=args.batch_id,
                api_url=args.api_url
            )

            if holiday_id:
                success_count += 1
                created_holidays.append({
                    'holiday_id': holiday_id,
                    'restaurant_id': restaurant['id'],
                    'restaurant_name': restaurant['name']
                })
            elif holiday_id is None and "già presente" not in str(holiday_id):
                # None = errore, non skippato
                failed_count += 1
                failed_restaurants.append(restaurant)

            # Piccola pausa per non sovraccaricare
            time.sleep(0.5)
        
        # 6. Riepilogo
        print("\n" + "=" * 80)
        print("📊 RIEPILOGO OPERAZIONE")
        print("=" * 80)
        print(f"✅ Successi: {success_count}/{len(restaurants)}")
        print(f"❌ Errori:   {failed_count}/{len(restaurants)}")

        if failed_restaurants:
            print("\n⚠️  Ristoranti con errori:")
            for r in failed_restaurants:
                print(f"   - {r['name']} (ID: {r['id']})")

        print("=" * 80)

        # Output JSON per il controller
        stats_json = {
            'total': len(restaurants),
            'success': success_count,
            'failed': failed_count,
            'created_holidays': created_holidays
        }
        print(f"\n__JSON_STATS__:{json.dumps(stats_json)}:__END_JSON__\n")

        # Return code: 0 se tutto ok, 1 se ci sono stati errori
        return 0 if failed_count == 0 else 1
        
    except KeyboardInterrupt:
        print("\n\n⚠️  Operazione interrotta dall'utente")
        return 1
    except Exception as e:
        print(f"\n❌ Errore fatale: {e}")
        import traceback
        traceback.print_exc()
        return 1
    finally:
        # Chiudi il browser
        print("\n🔒 Chiusura browser...")
        driver.quit()


if __name__ == "__main__":
    sys.exit(main())
