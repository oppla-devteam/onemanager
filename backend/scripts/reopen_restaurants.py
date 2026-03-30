#!/usr/bin/env python3
"""
Script per riaprire automaticamente i ristoranti OPPLA
Elimina le chiusure specificate dal database
"""

import sys
import argparse
import os
import psycopg2
import json
from datetime import datetime

# Database PostgreSQL OPPLA
DB_CONFIG = {
    'host': 'dpg-cktqf7enfb1c73ehil50-a.frankfurt-postgres.render.com',
    'port': 5432,
    'database': 'postgres_s4yp',
    'user': 'postgres_s4yp_user',
    'password': 'GYmJHlJg8Jye5TYeOl9OPZvyjOLM186m'
}


def delete_holidays(holiday_ids):
    """
    Elimina le chiusure specificate dal database OPPLA

    Args:
        holiday_ids: Lista di UUID delle chiusure da eliminare

    Returns:
        dict: Statistiche dell'operazione
    """
    if not holiday_ids:
        return {'total': 0, 'success': 0, 'failed': 0}

    print(f"\n🗑️  Eliminazione di {len(holiday_ids)} chiusure...\n")

    success_count = 0
    failed_count = 0
    failed_ids = []

    try:
        conn = psycopg2.connect(
            host=DB_CONFIG['host'],
            port=DB_CONFIG['port'],
            database=DB_CONFIG['database'],
            user=DB_CONFIG['user'],
            password=DB_CONFIG['password'],
            connect_timeout=10
        )

        cursor = conn.cursor()

        for i, holiday_id in enumerate(holiday_ids, 1):
            print(f"[{i}/{len(holiday_ids)}] 🗑️  Eliminazione chiusura {holiday_id[:8]}... ", end="", flush=True)

            try:
                # Elimina la chiusura
                cursor.execute("DELETE FROM holidays WHERE id = %s", (holiday_id,))
                conn.commit()

                if cursor.rowcount > 0:
                    print("✓")
                    success_count += 1
                else:
                    print("⚠️  (non trovata)")
                    failed_count += 1
                    failed_ids.append(holiday_id)

            except Exception as e:
                print(f"❌ {str(e)[:50]}")
                failed_count += 1
                failed_ids.append(holiday_id)
                conn.rollback()

        cursor.close()
        conn.close()

    except psycopg2.Error as e:
        print(f"\n❌ Errore connessione database: {e}")
        return {'total': len(holiday_ids), 'success': 0, 'failed': len(holiday_ids)}

    return {
        'total': len(holiday_ids),
        'success': success_count,
        'failed': failed_count,
        'failed_ids': failed_ids
    }


def main():
    """
    Funzione principale
    """
    parser = argparse.ArgumentParser(
        description='Riapertura massiva ristoranti OPPLA (elimina chiusure)'
    )
    parser.add_argument('--holiday-ids', required=True,
                       help='JSON array di UUID delle chiusure da eliminare')

    args = parser.parse_args()

    print("=" * 80)
    print("🔓 RIAPERTURA MASSIVA RISTORANTI OPPLA")
    print("=" * 80)

    # Parse JSON
    try:
        holiday_ids = json.loads(args.holiday_ids)
        if not isinstance(holiday_ids, list):
            print("❌ --holiday-ids deve essere un array JSON")
            return 1
    except json.JSONDecodeError as e:
        print(f"❌ Errore parsing JSON: {e}")
        return 1

    print(f"📋 Chiusure da eliminare: {len(holiday_ids)}")
    print("=" * 80)

    # Elimina le chiusure
    stats = delete_holidays(holiday_ids)

    # Riepilogo
    print("\n" + "=" * 80)
    print("📊 RIEPILOGO OPERAZIONE")
    print("=" * 80)
    print(f"✅ Successi: {stats['success']}/{stats['total']}")
    print(f"❌ Errori:   {stats['failed']}/{stats['total']}")

    if stats.get('failed_ids'):
        print("\n⚠️  Chiusure non eliminate:")
        for hid in stats['failed_ids']:
            print(f"   - {hid}")

    print("=" * 80)

    # Output JSON per il controller
    print(f"\n__JSON_STATS__:{json.dumps(stats)}:__END_JSON__\n")

    # Return code: 0 se tutto ok, 1 se ci sono stati errori
    return 0 if stats['failed'] == 0 else 1


if __name__ == "__main__":
    sys.exit(main())
