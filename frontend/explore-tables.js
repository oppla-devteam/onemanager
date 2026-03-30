// Esplora tabella users
import pkg from 'pg';
const { Client } = pkg;

const client = new Client({
  host: 'dpg-cl3r1n8t3kic73dh7110-a.frankfurt-postgres.render.com',
  port: 5432,
  database: 'postgres_staging_piqa',
  user: 'postgres_staging_piqa_user',
  password: 'Rn99Wpz7TZeBD91ZisltuAIzgKli56PR',
  ssl: { rejectUnauthorized: false }
});

async function exploreUsers() {
  await client.connect();
  
  // Struttura
  console.log('📋 Struttura tabella users:');
  const cols = await client.query(`
    SELECT column_name, data_type 
    FROM information_schema.columns 
    WHERE table_name='users' 
    ORDER BY ordinal_position
  `);
  cols.rows.forEach(c => console.log(`   - ${c.column_name} (${c.data_type})`));
  
  // Conteggio
  const count = await client.query('SELECT COUNT(*) as total FROM users');
  console.log(`\n📊 Record totali: ${count.rows[0].total}`);
  
  // Sample
  console.log('\n📄 Primi 2 record:');
  const sample = await client.query('SELECT * FROM users LIMIT 2');
  sample.rows.forEach((row, i) => {
    console.log(`\nRecord ${i+1}:`);
    console.log(JSON.stringify(row, null, 2));
  });
  
  // Esplora ristoranti
  console.log('\n\n📋 Struttura tabella restaurants:');
  const restCols = await client.query(`
    SELECT column_name, data_type 
    FROM information_schema.columns 
    WHERE table_name='restaurants' 
    ORDER BY ordinal_position
  `);
  restCols.rows.forEach(c => console.log(`   - ${c.column_name} (${c.data_type})`));
  
  const restCount = await client.query('SELECT COUNT(*) as total FROM restaurants');
  console.log(`\n📊 Ristoranti totali: ${restCount.rows[0].total}`);
  
  const restSample = await client.query('SELECT * FROM restaurants LIMIT 2');
  console.log('\n📄 Primi 2 ristoranti:');
  restSample.rows.forEach((row, i) => {
    console.log(`\nRistorante ${i+1}:`);
    console.log(JSON.stringify(row, null, 2));
  });
  
  await client.end();
}

exploreUsers();
