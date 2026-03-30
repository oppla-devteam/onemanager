import { PostgreSQLService } from '../services/database';

/**
 * Script di test per verificare la connessione al database PostgreSQL di Oppla
 * 
 * IMPORTANTE: Prima di eseguire questo test:
 * 1. Configura le credenziali in .env.local
 * 2. Assicurati di avere un utente read-only
 * 3. Verifica che il firewall permetta la connessione
 */

async function testOpplaConnection() {
  console.log('🔍 Test Connessione PostgreSQL Oppla');
  console.log('=' .repeat(50));
  
  const db = new PostgreSQLService();
  
  // Test 1: Verifica connessione
  console.log('\n1️⃣ Test connessione al database...');
  const isConnected = await db.testConnection();
  
  if (!isConnected) {
    console.error('❌ CONNESSIONE FALLITA');
    console.error('Possibili cause:');
    console.error('  - Credenziali errate in .env.local');
    console.error('  - Database non accessibile da questa IP');
    console.error('  - Firewall blocca la porta 5432');
    console.error('  - SSL non configurato correttamente');
    return;
  }
  
  console.log('CONNESSO AL DATABASE!');
  
  // Test 2: Recupera partners
  console.log('\n2️⃣ Test recupero partners...');
  try {
    const partners = await db.getAllClients();
    console.log(`Partners recuperati: ${partners.length}`);
    
    if (partners.length > 0) {
      console.log('\n📊 Esempio partner (primo record):');
      console.log(JSON.stringify(partners[0], null, 2));
    }
  } catch (error) {
    console.error('❌ Errore recupero partners:', error);
  }
  
  // Test 3: Statistiche
  console.log('\n3️⃣ Test statistiche...');
  try {
    const stats = await db.getClientStats();
    console.log('Statistiche ottenute:');
    console.log(`   - Partners totali: ${stats.total_clients}`);
    console.log(`   - Con email: ${stats.clients_with_email}`);
    console.log(`   - Con telefono: ${stats.clients_with_phone}`);
  } catch (error) {
    console.error('❌ Errore statistiche:', error);
  }
  
  // Test 4: Ricerca
  console.log('\n4️⃣ Test ricerca...');
  try {
    const searchResults = await db.searchClients('test');
    console.log(`Risultati ricerca "test": ${searchResults.length}`);
  } catch (error) {
    console.error('❌ Errore ricerca:', error);
  }
  
  console.log('\n' + '='.repeat(50));
  console.log('Test completato!');
}

// Esegui test
testOpplaConnection()
  .catch(error => {
    console.error('❌ Errore fatale:', error);
    process.exit(1);
  });
