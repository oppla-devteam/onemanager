// Test connessione PostgreSQL in sola lettura
// Esegui con: node test-db-connection.js

import pkg from 'pg';
const { Client } = pkg;

const config = {
  host: 'dpg-cl3r1n8t3kic73dh7110-a.frankfurt-postgres.render.com',
  port: 5432,
  database: 'postgres_staging_piqa',
  user: 'postgres_staging_piqa_user',
  password: 'Rn99Wpz7TZeBD91ZisltuAIzgKli56PR',
  ssl: {
    rejectUnauthorized: false
  }
};

async function testConnection() {
  console.log('🔍 Test Connessione PostgreSQL Oppla (SOLA LETTURA)');
  console.log('='.repeat(60));
  console.log(`📡 Host: ${config.host}`);
  console.log(`🗄️  Database: ${config.database}`);
  console.log(`👤 User: ${config.user}`);
  console.log('');

  const client = new Client(config);

  try {
    // Test 1: Connessione
    console.log('1️⃣ Connessione al database...');
    await client.connect();
    console.log('CONNESSO!');
    console.log('');

    // Test 2: Lista tabelle disponibili
    console.log('2️⃣ Recupero lista tabelle...');
    const tablesResult = await client.query(`
      SELECT table_name 
      FROM information_schema.tables 
      WHERE table_schema = 'public' 
      ORDER BY table_name;
    `);
    
    console.log(`Trovate ${tablesResult.rows.length} tabelle:`);
    tablesResult.rows.forEach(row => {
      console.log(`   - ${row.table_name}`);
    });
    console.log('');

    // Test 3: Cerca tabella partners/clients
    const partnersTable = tablesResult.rows.find(
      row => row.table_name.toLowerCase().includes('partner') || 
             row.table_name.toLowerCase().includes('client') ||
             row.table_name.toLowerCase().includes('user')
    );

    if (partnersTable) {
      console.log(`3️⃣ Trovata tabella potenziale: ${partnersTable.table_name}`);
      
      // Test 4: Struttura tabella
      const columnsResult = await client.query(`
        SELECT column_name, data_type 
        FROM information_schema.columns 
        WHERE table_name = $1 
        ORDER BY ordinal_position;
      `, [partnersTable.table_name]);
      
      console.log(`📋 Struttura tabella ${partnersTable.table_name}:`);
      columnsResult.rows.forEach(col => {
        console.log(`   - ${col.column_name} (${col.data_type})`);
      });
      console.log('');

      // Test 5: Conteggio record
      const countResult = await client.query(`
        SELECT COUNT(*) as total FROM ${partnersTable.table_name};
      `);
      console.log(`📊 Record totali: ${countResult.rows[0].total}`);
      console.log('');

      // Test 6: Primi 3 record
      const sampleResult = await client.query(`
        SELECT * FROM ${partnersTable.table_name} LIMIT 3;
      `);
      console.log(`📄 Primi 3 record:`);
      sampleResult.rows.forEach((row, i) => {
        console.log(`\n   Record ${i + 1}:`);
        console.log(JSON.stringify(row, null, 6));
      });
    } else {
      console.log('⚠️  Nessuna tabella partners/clients/users trovata');
      console.log('📋 Usa una delle tabelle sopra per configurare VITE_DB_TABLE');
    }

    console.log('');
    console.log('='.repeat(60));
    console.log('Test completato con successo!');
    console.log('🔒 Tutte le query sono state in SOLA LETTURA (SELECT only)');
    
  } catch (error) {
    console.error('');
    console.error('❌ ERRORE:', error.message);
    console.error('');
    console.error('Possibili cause:');
    console.error('  - Credenziali errate');
    console.error('  - Database non accessibile');
    console.error('  - Firewall blocca connessione');
    console.error('  - SSL non configurato correttamente');
  } finally {
    await client.end();
  }
}

testConnection();
