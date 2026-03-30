<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Seeder generato automaticamente il 2026-01-07 21:54:29
 * Contiene 0 transazioni Stripe e 220 application fees
 */
class StripeProductionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🔄 Importazione dati Stripe in produzione...');
        
        $transactions = array (
);
        
        $fees = array (
  0 => 
  array (
    'id' => 1,
    'stripe_fee_id' => 'fee_1Sn0afPEpkzElSu4Ryom1rO1',
    'amount' => '3.11',
    'currency' => 'EUR',
    'created_at_stripe' => '2026-01-07 21:47:44',
    'stripe_account_id' => 'acct_1OrHzGPEpkzElSu4',
    'partner_email' => 'andreadellomodarme@gmail.com',
    'partner_name' => 'PUNTO P DI ANDREA DELL\'OMODARME',
    'client_id' => 383,
    'charge_id' => 'py_1Sn0afPEpkzElSu4JtkpEQI7',
    'description' => 'andreadellomodarme@gmail.com - acct_1OrHzGPEpkzElSu4',
    'period_month' => '2026-01',
    'raw_data' => 
    array (
      'id' => 'fee_1Sn0afPEpkzElSu4Ryom1rO1',
      'object' => 'application_fee',
      'account' => 'acct_1OrHzGPEpkzElSu4',
      'amount' => 311,
      'amount_refunded' => 0,
      'application' => 'ca_Ox2Rdo90ojLoUICobfcdmeRJeF2Icbzs',
      'balance_transaction' => 'txn_1Sn0ahAns9lY52GQzYfRZ652',
      'charge' => 'py_1Sn0afPEpkzElSu4JtkpEQI7',
      'created' => 1767807133,
      'currency' => 'eur',
      'fee_source' => 
      array (
        'charge' => 'py_1Sn0afPEpkzElSu4JtkpEQI7',
        'type' => 'charge',
      ),
      'livemode' => true,
      'originating_transaction' => 'ch_3Sn0RuAns9lY52GQ1rYqdrAa',
      'refunded' => false,
      'refunds' => 
      array (
        'object' => 'list',
        'data' => 
        array (
        ),
        'has_more' => false,
        'total_count' => 0,
        'url' => '/v1/application_fees/fee_1Sn0afPEpkzElSu4Ryom1rO1/refunds',
      ),
    ),
    'created_at' => '2026-01-07 19:12:37',
    'updated_at' => '2026-01-07 20:47:44',
  ),
  1 => 
  array (
    'id' => 2,
    'stripe_fee_id' => 'fee_1SmfanPB8fwsTso3Z8krhK2C',
    'amount' => '2.78',
    'currency' => 'EUR',
    'created_at_stripe' => '2026-01-07 21:47:44',
    'stripe_account_id' => 'acct_1Oe1NCPB8fwsTso3',
    'partner_email' => 'alepizza2000@gmail.com',
    'partner_name' => 'PIZZA E TORTA DA ALEPIZZA DI BIANCHI ALESSIO',
    'client_id' => 336,
    'charge_id' => 'py_1SmfanPB8fwsTso36scMhf3k',
    'description' => 'alepizza2000@gmail.com - acct_1Oe1NCPB8fwsTso3',
    'period_month' => '2026-01',
    'raw_data' => 
    array (
      'id' => 'fee_1SmfanPB8fwsTso3Z8krhK2C',
      'object' => 'application_fee',
      'account' => 'acct_1Oe1NCPB8fwsTso3',
      'amount' => 278,
      'amount_refunded' => 278,
      'application' => 'ca_Ox2Rdo90ojLoUICobfcdmeRJeF2Icbzs',
      'balance_transaction' => 'txn_1SmfapAns9lY52GQa1ryYeb1',
      'charge' => 'py_1SmfanPB8fwsTso36scMhf3k',
      'created' => 1767726417,
      'currency' => 'eur',
      'fee_source' => 
      array (
        'charge' => 'py_1SmfanPB8fwsTso36scMhf3k',
        'type' => 'charge',
      ),
      'livemode' => true,
      'originating_transaction' => 'ch_3SmfYbAns9lY52GQ0Bs2c5ke',
      'refunded' => true,
      'refunds' => 
      array (
        'object' => 'list',
        'data' => 
        array (
          0 => 
          array (
            'id' => 'fr_1Smgi7PB8fwsTso3Tl0az1Im',
            'object' => 'fee_refund',
            'amount' => 278,
            'balance_transaction' => 'txn_1Smgi8Ans9lY52GQ4opf2yHU',
            'created' => 1767730715,
            'currency' => 'eur',
            'fee' => 'fee_1SmfanPB8fwsTso3Z8krhK2C',
            'metadata' => 
            array (
            ),
          ),
        ),
        'has_more' => false,
        'total_count' => 1,
        'url' => '/v1/application_fees/fee_1SmfanPB8fwsTso3Z8krhK2C/refunds',
      ),
    ),
    'created_at' => '2026-01-07 19:12:37',
    'updated_at' => '2026-01-07 20:47:44',
  ),
  2 => 
  array (
    'id' => 3,
    'stripe_fee_id' => 'fee_1SmfTpPIzlXORG3axri6FzmX',
    'amount' => '4.93',
    'currency' => 'EUR',
    'created_at_stripe' => '2026-01-07 21:47:44',
    'stripe_account_id' => 'acct_1OYpemPIzlXORG3a',
    'partner_email' => 'feusrl.2019@gmail.com',
    'partner_name' => 'Feu Srl',
    'client_id' => 301,
    'charge_id' => 'py_1SmfTpPIzlXORG3arIrQyU2F',
    'description' => 'feusrl.2019@gmail.com - acct_1OYpemPIzlXORG3a',
    'period_month' => '2026-01',
    'raw_data' => 
    array (
      'id' => 'fee_1SmfTpPIzlXORG3axri6FzmX',
      'object' => 'application_fee',
      'account' => 'acct_1OYpemPIzlXORG3a',
      'amount' => 493,
      'amount_refunded' => 0,
      'application' => 'ca_Ox2Rdo90ojLoUICobfcdmeRJeF2Icbzs',
      'balance_transaction' => 'txn_1SmfTsAns9lY52GQRKHNoiqS',
      'charge' => 'py_1SmfTpPIzlXORG3arIrQyU2F',
      'created' => 1767725985,
      'currency' => 'eur',
      'fee_source' => 
      array (
        'charge' => 'py_1SmfTpPIzlXORG3arIrQyU2F',
        'type' => 'charge',
      ),
      'livemode' => true,
      'originating_transaction' => 'ch_3SmfSJAns9lY52GQ0nNPCLeF',
      'refunded' => false,
      'refunds' => 
      array (
        'object' => 'list',
        'data' => 
        array (
        ),
        'has_more' => false,
        'total_count' => 0,
        'url' => '/v1/application_fees/fee_1SmfTpPIzlXORG3axri6FzmX/refunds',
      ),
    ),
    'created_at' => '2026-01-07 19:12:38',
    'updated_at' => '2026-01-07 20:47:44',
  ),
  3 => 
  array (
    'id' => 4,
    'stripe_fee_id' => 'fee_1SmczdPB8fwsTso3EMZmks9q',
    'amount' => '2.77',
    'currency' => 'EUR',
    'created_at_stripe' => '2026-01-07 21:47:44',
    'stripe_account_id' => 'acct_1Oe1NCPB8fwsTso3',
    'partner_email' => 'alepizza2000@gmail.com',
    'partner_name' => 'PIZZA E TORTA DA ALEPIZZA DI BIANCHI ALESSIO',
    'client_id' => 336,
    'charge_id' => 'py_1SmczdPB8fwsTso3WC5PUpEP',
    'description' => 'alepizza2000@gmail.com - acct_1Oe1NCPB8fwsTso3',
    'period_month' => '2026-01',
    'raw_data' => 
    array (
      'id' => 'fee_1SmczdPB8fwsTso3EMZmks9q',
      'object' => 'application_fee',
      'account' => 'acct_1Oe1NCPB8fwsTso3',
      'amount' => 277,
      'amount_refunded' => 0,
      'application' => 'ca_Ox2Rdo90ojLoUICobfcdmeRJeF2Icbzs',
      'balance_transaction' => 'txn_1SmczgAns9lY52GQsTOWOr05',
      'charge' => 'py_1SmczdPB8fwsTso3WC5PUpEP',
      'created' => 1767716425,
      'currency' => 'eur',
      'fee_source' => 
      array (
        'charge' => 'py_1SmczdPB8fwsTso3WC5PUpEP',
        'type' => 'charge',
      ),
      'livemode' => true,
      'originating_transaction' => 'ch_3SmXOEAns9lY52GQ0Q0M5yPm',
      'refunded' => false,
      'refunds' => 
      array (
        'object' => 'list',
        'data' => 
        array (
        ),
        'has_more' => false,
        'total_count' => 0,
        'url' => '/v1/application_fees/fee_1SmczdPB8fwsTso3EMZmks9q/refunds',
      ),
    ),
    'created_at' => '2026-01-07 19:12:39',
    'updated_at' => '2026-01-07 20:47:44',
  ),
  4 => 
  array (
    'id' => 5,
    'stripe_fee_id' => 'fee_1SmHZnPB7qjhlfVaiqk2hmgH',
    'amount' => '2.98',
    'currency' => 'EUR',
    'created_at_stripe' => '2026-01-07 21:47:44',
    'stripe_account_id' => 'acct_1OrlPGPB7qjhlfVa',
    'partner_email' => 'laboratoriodellapizza@gmail.com',
    'partner_name' => 'Macrì Domenico',
    'client_id' => 325,
    'charge_id' => 'py_1SmHZnPB7qjhlfVaNUAS3WJU',
    'description' => 'laboratoriodellapizza@gmail.com - acct_1OrlPGPB7qjhlfVa',
    'period_month' => '2026-01',
    'raw_data' => 
    array (
      'id' => 'fee_1SmHZnPB7qjhlfVaiqk2hmgH',
      'object' => 'application_fee',
      'account' => 'acct_1OrlPGPB7qjhlfVa',
      'amount' => 298,
      'amount_refunded' => 0,
      'application' => 'ca_Ox2Rdo90ojLoUICobfcdmeRJeF2Icbzs',
      'balance_transaction' => 'txn_1SmHZpAns9lY52GQ7KQs2ljS',
      'charge' => 'py_1SmHZnPB7qjhlfVaNUAS3WJU',
      'created' => 1767634099,
      'currency' => 'eur',
      'fee_source' => 
      array (
        'charge' => 'py_1SmHZnPB7qjhlfVaNUAS3WJU',
        'type' => 'charge',
      ),
      'livemode' => true,
      'originating_transaction' => 'ch_3SmHQwAns9lY52GQ1Z0u4Y1a',
      'refunded' => false,
      'refunds' => 
      array (
        'object' => 'list',
        'data' => 
        array (
        ),
        'has_more' => false,
        'total_count' => 0,
        'url' => '/v1/application_fees/fee_1SmHZnPB7qjhlfVaiqk2hmgH/refunds',
      ),
    ),
    'created_at' => '2026-01-07 19:12:39',
    'updated_at' => '2026-01-07 20:47:44',
  ),
  5 => 
  array (
    'id' => 6,
    'stripe_fee_id' => 'fee_1SmH7UPEpkzElSu4BO6BgxRb',
    'amount' => '2.82',
    'currency' => 'EUR',
    'created_at_stripe' => '2026-01-07 21:47:44',
    'stripe_account_id' => 'acct_1OrHzGPEpkzElSu4',
    'partner_email' => 'andreadellomodarme@gmail.com',
    'partner_name' => 'PUNTO P DI ANDREA DELL\'OMODARME',
    'client_id' => 383,
    'charge_id' => 'py_1SmH7TPEpkzElSu4KeLINhHV',
    'description' => 'andreadellomodarme@gmail.com - acct_1OrHzGPEpkzElSu4',
    'period_month' => '2026-01',
    'raw_data' => 
    array (
      'id' => 'fee_1SmH7UPEpkzElSu4BO6BgxRb',
      'object' => 'application_fee',
      'account' => 'acct_1OrHzGPEpkzElSu4',
      'amount' => 282,
      'amount_refunded' => 0,
      'application' => 'ca_Ox2Rdo90ojLoUICobfcdmeRJeF2Icbzs',
      'balance_transaction' => 'txn_1SmH7WAns9lY52GQNxkDjw3X',
      'charge' => 'py_1SmH7TPEpkzElSu4KeLINhHV',
      'created' => 1767632344,
      'currency' => 'eur',
      'fee_source' => 
      array (
        'charge' => 'py_1SmH7TPEpkzElSu4KeLINhHV',
        'type' => 'charge',
      ),
      'livemode' => true,
      'originating_transaction' => 'ch_3SmFl8Ans9lY52GQ1gHZAfYB',
      'refunded' => false,
      'refunds' => 
      array (
        'object' => 'list',
        'data' => 
        array (
        ),
        'has_more' => false,
        'total_count' => 0,
        'url' => '/v1/application_fees/fee_1SmH7UPEpkzElSu4BO6BgxRb/refunds',
      ),
    ),
    'created_at' => '2026-01-07 19:12:40',
    'updated_at' => '2026-01-07 20:47:44',
  ),
  6 => 
  array (
    'id' => 7,
    'stripe_fee_id' => 'fee_1SmH6SPEpkzElSu4VTdeT24Z',
    'amount' => '2.98',
    'currency' => 'EUR',
    'created_at_stripe' => '2026-01-07 21:47:44',
    'stripe_account_id' => 'acct_1OrHzGPEpkzElSu4',
    'partner_email' => 'andreadellomodarme@gmail.com',
    'partner_name' => 'PUNTO P DI ANDREA DELL\'OMODARME',
    'client_id' => 383,
    'charge_id' => 'py_1SmH6SPEpkzElSu465EQeBju',
    'description' => 'andreadellomodarme@gmail.com - acct_1OrHzGPEpkzElSu4',
    'period_month' => '2026-01',
    'raw_data' => 
    array (
      'id' => 'fee_1SmH6SPEpkzElSu4VTdeT24Z',
      'object' => 'application_fee',
      'account' => 'acct_1OrHzGPEpkzElSu4',
      'amount' => 298,
      'amount_refunded' => 0,
      'application' => 'ca_Ox2Rdo90ojLoUICobfcdmeRJeF2Icbzs',
      'balance_transaction' => 'txn_1SmH6UAns9lY52GQPOphTqOr',
      'charge' => 'py_1SmH6SPEpkzElSu465EQeBju',
      'created' => 1767632280,
      'currency' => 'eur',
      'fee_source' => 
      array (
        'charge' => 'py_1SmH6SPEpkzElSu465EQeBju',
        'type' => 'charge',
      ),
      'livemode' => true,
      'originating_transaction' => 'ch_3SmFOsAns9lY52GQ1cJg5ScK',
      'refunded' => false,
      'refunds' => 
      array (
        'object' => 'list',
        'data' => 
        array (
        ),
        'has_more' => false,
        'total_count' => 0,
        'url' => '/v1/application_fees/fee_1SmH6SPEpkzElSu4VTdeT24Z/refunds',
      ),
    ),
    'created_at' => '2026-01-07 19:12:40',
    'updated_at' => '2026-01-07 20:47:44',
  ),
  7 => 
  array (
    'id' => 8,
    'stripe_fee_id' => 'fee_1SmH5BPEpkzElSu4BuiwfKiY',
    'amount' => '3.21',
    'currency' => 'EUR',
    'created_at_stripe' => '2026-01-07 21:47:44',
    'stripe_account_id' => 'acct_1OrHzGPEpkzElSu4',
    'partner_email' => 'andreadellomodarme@gmail.com',
    'partner_name' => 'PUNTO P DI ANDREA DELL\'OMODARME',
    'client_id' => 383,
    'charge_id' => 'py_1SmH5BPEpkzElSu4xWihXYqh',
    'description' => 'andreadellomodarme@gmail.com - acct_1OrHzGPEpkzElSu4',
    'period_month' => '2026-01',
    'raw_data' => 
    array (
      'id' => 'fee_1SmH5BPEpkzElSu4BuiwfKiY',
      'object' => 'application_fee',
      'account' => 'acct_1OrHzGPEpkzElSu4',
      'amount' => 321,
      'amount_refunded' => 0,
      'application' => 'ca_Ox2Rdo90ojLoUICobfcdmeRJeF2Icbzs',
      'balance_transaction' => 'txn_1SmH5EAns9lY52GQ3VOPP9iU',
      'charge' => 'py_1SmH5BPEpkzElSu4xWihXYqh',
      'created' => 1767632201,
      'currency' => 'eur',
      'fee_source' => 
      array (
        'charge' => 'py_1SmH5BPEpkzElSu4xWihXYqh',
        'type' => 'charge',
      ),
      'livemode' => true,
      'originating_transaction' => 'ch_3SmE3BAns9lY52GQ1N6MlZNB',
      'refunded' => false,
      'refunds' => 
      array (
        'object' => 'list',
        'data' => 
        array (
        ),
        'has_more' => false,
        'total_count' => 0,
        'url' => '/v1/application_fees/fee_1SmH5BPEpkzElSu4BuiwfKiY/refunds',
      ),
    ),
    'created_at' => '2026-01-07 19:12:41',
    'updated_at' => '2026-01-07 20:47:44',
  ),
  8 => 
  array (
    'id' => 9,
    'stripe_fee_id' => 'fee_1SmH3rPEpkzElSu4VulDlipV',
    'amount' => '3.96',
    'currency' => 'EUR',
    'created_at_stripe' => '2026-01-07 21:47:44',
    'stripe_account_id' => 'acct_1OrHzGPEpkzElSu4',
    'partner_email' => 'andreadellomodarme@gmail.com',
    'partner_name' => 'PUNTO P DI ANDREA DELL\'OMODARME',
    'client_id' => 383,
    'charge_id' => 'py_1SmH3rPEpkzElSu4YLhHUNCy',
    'description' => 'andreadellomodarme@gmail.com - acct_1OrHzGPEpkzElSu4',
    'period_month' => '2026-01',
    'raw_data' => 
    array (
      'id' => 'fee_1SmH3rPEpkzElSu4VulDlipV',
      'object' => 'application_fee',
      'account' => 'acct_1OrHzGPEpkzElSu4',
      'amount' => 396,
      'amount_refunded' => 0,
      'application' => 'ca_Ox2Rdo90ojLoUICobfcdmeRJeF2Icbzs',
      'balance_transaction' => 'txn_1SmH3uAns9lY52GQPTAvQwiZ',
      'charge' => 'py_1SmH3rPEpkzElSu4YLhHUNCy',
      'created' => 1767632119,
      'currency' => 'eur',
      'fee_source' => 
      array (
        'charge' => 'py_1SmH3rPEpkzElSu4YLhHUNCy',
        'type' => 'charge',
      ),
      'livemode' => true,
      'originating_transaction' => 'ch_3SmDmiAns9lY52GQ1dprWstA',
      'refunded' => false,
      'refunds' => 
      array (
        'object' => 'list',
        'data' => 
        array (
        ),
        'has_more' => false,
        'total_count' => 0,
        'url' => '/v1/application_fees/fee_1SmH3rPEpkzElSu4VulDlipV/refunds',
      ),
    ),
    'created_at' => '2026-01-07 19:12:42',
    'updated_at' => '2026-01-07 20:47:44',
  ),
  9 => 
  array (
    'id' => 10,
    'stripe_fee_id' => 'fee_1Slw2FAcaExTZKe8RQx2PsTT',
    'amount' => '3.90',
    'currency' => 'EUR',
    'created_at_stripe' => '2026-01-07 21:47:44',
    'stripe_account_id' => 'acct_1QsMHWAcaExTZKe8',
    'partner_email' => 'nclcucc@gmail.com',
    'partner_name' => 'La Loggia sul Mare SRL',
    'client_id' => 322,
    'charge_id' => 'py_1Slw2FAcaExTZKe8XSnZg7zj',
    'description' => 'nclcucc@gmail.com - acct_1QsMHWAcaExTZKe8',
    'period_month' => '2026-01',
    'raw_data' => 
    array (
      'id' => 'fee_1Slw2FAcaExTZKe8RQx2PsTT',
      'object' => 'application_fee',
      'account' => 'acct_1QsMHWAcaExTZKe8',
      'amount' => 390,
      'amount_refunded' => 0,
      'application' => 'ca_Ox2Rdo90ojLoUICobfcdmeRJeF2Icbzs',
      'balance_transaction' => 'txn_1Slw2HAns9lY52GQg1RCNVRz',
      'charge' => 'py_1Slw2FAcaExTZKe8XSnZg7zj',
      'created' => 1767551295,
      'currency' => 'eur',
      'fee_source' => 
      array (
        'charge' => 'py_1Slw2FAcaExTZKe8XSnZg7zj',
        'type' => 'charge',
      ),
      'livemode' => true,
      'originating_transaction' => 'ch_3SluNTAns9lY52GQ1nAFhf3e',
      'refunded' => false,
      'refunds' => 
      array (
        'object' => 'list',
        'data' => 
        array (
        ),
        'has_more' => false,
        'total_count' => 0,
        'url' => '/v1/application_fees/fee_1Slw2FAcaExTZKe8RQx2PsTT/refunds',
      ),
    ),
    'created_at' => '2026-01-07 19:12:42',
    'updated_at' => '2026-01-07 20:47:44',
  ),
  10 => 
  array (
    'id' => 11,
    'stripe_fee_id' => 'fee_1Slw1qAcaExTZKe8MMQwwPHh',
    'amount' => '3.94',
    'currency' => 'EUR',
    'created_at_stripe' => '2026-01-07 21:47:44',
    'stripe_account_id' => 'acct_1QsMHWAcaExTZKe8',
    'partner_email' => 'nclcucc@gmail.com',
    'partner_name' => 'La Loggia sul Mare SRL',
    'client_id' => 322,
    'charge_id' => 'py_1Slw1qAcaExTZKe8sq5txodV',
    'description' => 'nclcucc@gmail.com - acct_1QsMHWAcaExTZKe8',
    'period_month' => '2026-01',
    'raw_data' => 
    array (
      'id' => 'fee_1Slw1qAcaExTZKe8MMQwwPHh',
      'object' => 'application_fee',
      'account' => 'acct_1QsMHWAcaExTZKe8',
      'amount' => 394,
      'amount_refunded' => 0,
      'application' => 'ca_Ox2Rdo90ojLoUICobfcdmeRJeF2Icbzs',
      'balance_transaction' => 'txn_1Slw1tAns9lY52GQ5JgowzZZ',
      'charge' => 'py_1Slw1qAcaExTZKe8sq5txodV',
      'created' => 1767551270,
      'currency' => 'eur',
      'fee_source' => 
      array (
        'charge' => 'py_1Slw1qAcaExTZKe8sq5txodV',
        'type' => 'charge',
      ),
      'livemode' => true,
      'originating_transaction' => 'ch_3SluuDAns9lY52GQ1MsmWbZ5',
      'refunded' => false,
      'refunds' => 
      array (
        'object' => 'list',
        'data' => 
        array (
        ),
        'has_more' => false,
        'total_count' => 0,
        'url' => '/v1/application_fees/fee_1Slw1qAcaExTZKe8MMQwwPHh/refunds',
      ),
    ),
    'created_at' => '2026-01-07 19:12:43',
    'updated_at' => '2026-01-07 20:47:44',
  ),
  11 => 
  array (
    'id' => 12,
    'stripe_fee_id' => 'fee_1SlvpcPDtObfqosD5PAcXNuY',
    'amount' => '4.74',
    'currency' => 'EUR',
    'created_at_stripe' => '2026-01-07 21:47:44',
    'stripe_account_id' => 'acct_1OeD5cPDtObfqosD',
    'partner_email' => 'stripe@sushinoexperience.com',
    'partner_name' => 'Sushino SRLS',
    'client_id' => 384,
    'charge_id' => 'py_1SlvpcPDtObfqosD2kSBo2u4',
    'description' => 'stripe@sushinoexperience.com - acct_1OeD5cPDtObfqosD',
    'period_month' => '2026-01',
    'raw_data' => 
    array (
      'id' => 'fee_1SlvpcPDtObfqosD5PAcXNuY',
      'object' => 'application_fee',
      'account' => 'acct_1OeD5cPDtObfqosD',
      'amount' => 474,
      'amount_refunded' => 0,
      'application' => 'ca_Ox2Rdo90ojLoUICobfcdmeRJeF2Icbzs',
      'balance_transaction' => 'txn_1SlvpfAns9lY52GQUQ0Yaho0',
      'charge' => 'py_1SlvpcPDtObfqosD2kSBo2u4',
      'created' => 1767550512,
      'currency' => 'eur',
      'fee_source' => 
      array (
        'charge' => 'py_1SlvpcPDtObfqosD2kSBo2u4',
        'type' => 'charge',
      ),
      'livemode' => true,
      'originating_transaction' => 'ch_3SlvHwAns9lY52GQ0zSQ655i',
      'refunded' => false,
      'refunds' => 
      array (
        'object' => 'list',
        'data' => 
        array (
        ),
        'has_more' => false,
        'total_count' => 0,
        'url' => '/v1/application_fees/fee_1SlvpcPDtObfqosD5PAcXNuY/refunds',
      ),
    ),
    'created_at' => '2026-01-07 19:12:43',
    'updated_at' => '2026-01-07 20:47:44',
  ),
  12 => 
  array (
    'id' => 13,
    'stripe_fee_id' => 'fee_1SlshRPEpkzElSu4WPO4NjRh',
    'amount' => '3.08',
    'currency' => 'EUR',
    'created_at_stripe' => '2026-01-07 21:47:44',
    'stripe_account_id' => 'acct_1OrHzGPEpkzElSu4',
    'partner_email' => 'andreadellomodarme@gmail.com',
    'partner_name' => 'PUNTO P DI ANDREA DELL\'OMODARME',
    'client_id' => 383,
    'charge_id' => 'py_1SlshRPEpkzElSu4dl3qZBGv',
    'description' => 'andreadellomodarme@gmail.com - acct_1OrHzGPEpkzElSu4',
    'period_month' => '2026-01',
    'raw_data' => 
    array (
      'id' => 'fee_1SlshRPEpkzElSu4WPO4NjRh',
      'object' => 'application_fee',
      'account' => 'acct_1OrHzGPEpkzElSu4',
      'amount' => 308,
      'amount_refunded' => 0,
      'application' => 'ca_Ox2Rdo90ojLoUICobfcdmeRJeF2Icbzs',
      'balance_transaction' => 'txn_1SlshUAns9lY52GQo4UupQGo',
      'charge' => 'py_1SlshRPEpkzElSu4dl3qZBGv',
      'created' => 1767538473,
      'currency' => 'eur',
      'fee_source' => 
      array (
        'charge' => 'py_1SlshRPEpkzElSu4dl3qZBGv',
        'type' => 'charge',
      ),
      'livemode' => true,
      'originating_transaction' => 'ch_3SlpdnAns9lY52GQ0xLs5VrK',
      'refunded' => false,
      'refunds' => 
      array (
        'object' => 'list',
        'data' => 
        array (
        ),
        'has_more' => false,
        'total_count' => 0,
        'url' => '/v1/application_fees/fee_1SlshRPEpkzElSu4WPO4NjRh/refunds',
      ),
    ),
    'created_at' => '2026-01-07 19:12:44',
    'updated_at' => '2026-01-07 20:47:44',
  ),
  13 => 
  array (
    'id' => 14,
    'stripe_fee_id' => 'fee_1SlacuPIzlXORG3aSdjeGXLX',
    'amount' => '3.64',
    'currency' => 'EUR',
    'created_at_stripe' => '2026-01-07 21:47:44',
    'stripe_account_id' => 'acct_1OYpemPIzlXORG3a',
    'partner_email' => 'feusrl.2019@gmail.com',
    'partner_name' => 'Feu Srl',
    'client_id' => 301,
    'charge_id' => 'py_1SlacuPIzlXORG3aHiD4qAcX',
    'description' => 'feusrl.2019@gmail.com - acct_1OYpemPIzlXORG3a',
    'period_month' => '2026-01',
    'raw_data' => 
    array (
      'id' => 'fee_1SlacuPIzlXORG3aSdjeGXLX',
      'object' => 'application_fee',
      'account' => 'acct_1OYpemPIzlXORG3a',
      'amount' => 364,
      'amount_refunded' => 0,
      'application' => 'ca_Ox2Rdo90ojLoUICobfcdmeRJeF2Icbzs',
      'balance_transaction' => 'txn_1SlacwAns9lY52GQpGCGOejH',
      'charge' => 'py_1SlacuPIzlXORG3aHiD4qAcX',
      'created' => 1767469000,
      'currency' => 'eur',
      'fee_source' => 
      array (
        'charge' => 'py_1SlacuPIzlXORG3aHiD4qAcX',
        'type' => 'charge',
      ),
      'livemode' => true,
      'originating_transaction' => 'ch_3SlaNyAns9lY52GQ0y9ozMzU',
      'refunded' => false,
      'refunds' => 
      array (
        'object' => 'list',
        'data' => 
        array (
        ),
        'has_more' => false,
        'total_count' => 0,
        'url' => '/v1/application_fees/fee_1SlacuPIzlXORG3aSdjeGXLX/refunds',
      ),
    ),
    'created_at' => '2026-01-07 19:12:45',
    'updated_at' => '2026-01-07 20:47:44',
  ),
  14 => 
  array (
    'id' => 15,
    'stripe_fee_id' => 'fee_1SlZaWPFSTNU0nUG1ojFCloP',
    'amount' => '2.92',
    'currency' => 'EUR',
    'created_at_stripe' => '2026-01-07 21:47:44',
    'stripe_account_id' => 'acct_1OtvsmPFSTNU0nUG',
    'partner_email' => 'fortipizza@gmail.com',
    'partner_name' => 'Forti Pizza e Torta',
    'client_id' => 303,
    'charge_id' => 'py_1SlZaVPFSTNU0nUGLA4Di5w8',
    'description' => 'fortipizza@gmail.com - acct_1OtvsmPFSTNU0nUG',
    'period_month' => '2026-01',
    'raw_data' => 
    array (
      'id' => 'fee_1SlZaWPFSTNU0nUG1ojFCloP',
      'object' => 'application_fee',
      'account' => 'acct_1OtvsmPFSTNU0nUG',
      'amount' => 292,
      'amount_refunded' => 0,
      'application' => 'ca_Ox2Rdo90ojLoUICobfcdmeRJeF2Icbzs',
      'balance_transaction' => 'txn_1SlZaYAns9lY52GQMkTWUi6v',
      'charge' => 'py_1SlZaVPFSTNU0nUGLA4Di5w8',
      'created' => 1767465008,
      'currency' => 'eur',
      'fee_source' => 
      array (
        'charge' => 'py_1SlZaVPFSTNU0nUGLA4Di5w8',
        'type' => 'charge',
      ),
      'livemode' => true,
      'originating_transaction' => 'ch_3SlYvwAns9lY52GQ1e0i0Pye',
      'refunded' => false,
      'refunds' => 
      array (
        'object' => 'list',
        'data' => 
        array (
        ),
        'has_more' => false,
        'total_count' => 0,
        'url' => '/v1/application_fees/fee_1SlZaWPFSTNU0nUG1ojFCloP/refunds',
      ),
    ),
    'created_at' => '2026-01-07 19:12:45',
    'updated_at' => '2026-01-07 20:47:44',
  ),
  15 => 
  array (
    'id' => 16,
    'stripe_fee_id' => 'fee_1SlZ0bPIzlXORG3aQel21iuU',
    'amount' => '5.18',
    'currency' => 'EUR',
    'created_at_stripe' => '2026-01-07 21:47:44',
    'stripe_account_id' => 'acct_1OYpemPIzlXORG3a',
    'partner_email' => 'feusrl.2019@gmail.com',
    'partner_name' => 'Feu Srl',
    'client_id' => 301,
    'charge_id' => 'py_1SlZ0bPIzlXORG3aspN1jB1k',
    'description' => 'feusrl.2019@gmail.com - acct_1OYpemPIzlXORG3a',
    'period_month' => '2026-01',
    'raw_data' => 
    array (
      'id' => 'fee_1SlZ0bPIzlXORG3aQel21iuU',
      'object' => 'application_fee',
      'account' => 'acct_1OYpemPIzlXORG3a',
      'amount' => 518,
      'amount_refunded' => 0,
      'application' => 'ca_Ox2Rdo90ojLoUICobfcdmeRJeF2Icbzs',
      'balance_transaction' => 'txn_1SlZ0dAns9lY52GQV5SnGQFa',
      'charge' => 'py_1SlZ0bPIzlXORG3aspN1jB1k',
      'created' => 1767462781,
      'currency' => 'eur',
      'fee_source' => 
      array (
        'charge' => 'py_1SlZ0bPIzlXORG3aspN1jB1k',
        'type' => 'charge',
      ),
      'livemode' => true,
      'originating_transaction' => 'ch_3SlYnOAns9lY52GQ1Ncqvroc',
      'refunded' => false,
      'refunds' => 
      array (
        'object' => 'list',
        'data' => 
        array (
        ),
        'has_more' => false,
        'total_count' => 0,
        'url' => '/v1/application_fees/fee_1SlZ0bPIzlXORG3aQel21iuU/refunds',
      ),
    ),
    'created_at' => '2026-01-07 19:12:46',
    'updated_at' => '2026-01-07 20:47:44',
  ),
  16 => 
  array (
    'id' => 17,
    'stripe_fee_id' => 'fee_1SlYgHPIzlXORG3aoUFTA2XY',
    'amount' => '4.86',
    'currency' => 'EUR',
    'created_at_stripe' => '2026-01-07 21:47:44',
    'stripe_account_id' => 'acct_1OYpemPIzlXORG3a',
    'partner_email' => 'feusrl.2019@gmail.com',
    'partner_name' => 'Feu Srl',
    'client_id' => 301,
    'charge_id' => 'py_1SlYgHPIzlXORG3aiYR3jyUY',
    'description' => 'feusrl.2019@gmail.com - acct_1OYpemPIzlXORG3a',
    'period_month' => '2026-01',
    'raw_data' => 
    array (
      'id' => 'fee_1SlYgHPIzlXORG3aoUFTA2XY',
      'object' => 'application_fee',
      'account' => 'acct_1OYpemPIzlXORG3a',
      'amount' => 486,
      'amount_refunded' => 0,
      'application' => 'ca_Ox2Rdo90ojLoUICobfcdmeRJeF2Icbzs',
      'balance_transaction' => 'txn_1SlYgKAns9lY52GQ7Yp7JxPD',
      'charge' => 'py_1SlYgHPIzlXORG3aiYR3jyUY',
      'created' => 1767461521,
      'currency' => 'eur',
      'fee_source' => 
      array (
        'charge' => 'py_1SlYgHPIzlXORG3aiYR3jyUY',
        'type' => 'charge',
      ),
      'livemode' => true,
      'originating_transaction' => 'ch_3SlWhNAns9lY52GQ0vySMqLx',
      'refunded' => false,
      'refunds' => 
      array (
        'object' => 'list',
        'data' => 
        array (
        ),
        'has_more' => false,
        'total_count' => 0,
        'url' => '/v1/application_fees/fee_1SlYgHPIzlXORG3aoUFTA2XY/refunds',
      ),
    ),
    'created_at' => '2026-01-07 19:12:47',
    'updated_at' => '2026-01-07 20:47:44',
  ),
  17 => 
  array (
    'id' => 18,
    'stripe_fee_id' => 'fee_1SlYCePEpkzElSu4yiVIWKz3',
    'amount' => '2.84',
    'currency' => 'EUR',
    'created_at_stripe' => '2026-01-07 21:47:44',
    'stripe_account_id' => 'acct_1OrHzGPEpkzElSu4',
    'partner_email' => 'andreadellomodarme@gmail.com',
    'partner_name' => 'PUNTO P DI ANDREA DELL\'OMODARME',
    'client_id' => 383,
    'charge_id' => 'py_1SlYCdPEpkzElSu4q5xnC4J6',
    'description' => 'andreadellomodarme@gmail.com - acct_1OrHzGPEpkzElSu4',
    'period_month' => '2026-01',
    'raw_data' => 
    array (
      'id' => 'fee_1SlYCePEpkzElSu4yiVIWKz3',
      'object' => 'application_fee',
      'account' => 'acct_1OrHzGPEpkzElSu4',
      'amount' => 284,
      'amount_refunded' => 0,
      'application' => 'ca_Ox2Rdo90ojLoUICobfcdmeRJeF2Icbzs',
      'balance_transaction' => 'txn_1SlYCgAns9lY52GQwOVTfJXY',
      'charge' => 'py_1SlYCdPEpkzElSu4q5xnC4J6',
      'created' => 1767459684,
      'currency' => 'eur',
      'fee_source' => 
      array (
        'charge' => 'py_1SlYCdPEpkzElSu4q5xnC4J6',
        'type' => 'charge',
      ),
      'livemode' => true,
      'originating_transaction' => 'ch_3SlYAVAns9lY52GQ0e3iRzPf',
      'refunded' => false,
      'refunds' => 
      array (
        'object' => 'list',
        'data' => 
        array (
        ),
        'has_more' => false,
        'total_count' => 0,
        'url' => '/v1/application_fees/fee_1SlYCePEpkzElSu4yiVIWKz3/refunds',
      ),
    ),
    'created_at' => '2026-01-07 19:12:47',
    'updated_at' => '2026-01-07 20:47:44',
  ),
  18 => 
  array (
    'id' => 19,
    'stripe_fee_id' => 'fee_1SlXzdPEpkzElSu4efQUCspI',
    'amount' => '2.98',
    'currency' => 'EUR',
    'created_at_stripe' => '2026-01-07 21:47:44',
    'stripe_account_id' => 'acct_1OrHzGPEpkzElSu4',
    'partner_email' => 'andreadellomodarme@gmail.com',
    'partner_name' => 'PUNTO P DI ANDREA DELL\'OMODARME',
    'client_id' => 383,
    'charge_id' => 'py_1SlXzcPEpkzElSu4MWpqMaGi',
    'description' => 'andreadellomodarme@gmail.com - acct_1OrHzGPEpkzElSu4',
    'period_month' => '2026-01',
    'raw_data' => 
    array (
      'id' => 'fee_1SlXzdPEpkzElSu4efQUCspI',
      'object' => 'application_fee',
      'account' => 'acct_1OrHzGPEpkzElSu4',
      'amount' => 298,
      'amount_refunded' => 0,
      'application' => 'ca_Ox2Rdo90ojLoUICobfcdmeRJeF2Icbzs',
      'balance_transaction' => 'txn_1SlXzfAns9lY52GQ9pAiFEuU',
      'charge' => 'py_1SlXzcPEpkzElSu4MWpqMaGi',
      'created' => 1767458877,
      'currency' => 'eur',
      'fee_source' => 
      array (
        'charge' => 'py_1SlXzcPEpkzElSu4MWpqMaGi',
        'type' => 'charge',
      ),
      'livemode' => true,
      'originating_transaction' => 'ch_3SlXuPAns9lY52GQ0WkwhkxG',
      'refunded' => false,
      'refunds' => 
      array (
        'object' => 'list',
        'data' => 
        array (
        ),
        'has_more' => false,
        'total_count' => 0,
        'url' => '/v1/application_fees/fee_1SlXzdPEpkzElSu4efQUCspI/refunds',
      ),
    ),
    'created_at' => '2026-01-07 19:12:48',
    'updated_at' => '2026-01-07 20:47:44',
  ),
  19 => 
  array (
    'id' => 20,
    'stripe_fee_id' => 'fee_1SlWhmPCrwFqsIfaNvNzCtWM',
    'amount' => '3.06',
    'currency' => 'EUR',
    'created_at_stripe' => '2026-01-07 21:47:44',
    'stripe_account_id' => 'acct_1OhbRBPCrwFqsIfa',
    'partner_email' => 'maeva2000@inwind.it',
    'partner_name' => 'SPEEDY PIZZA DI MASSIMILIANO SILVESTRI',
    'client_id' => 358,
    'charge_id' => 'py_1SlWhlPCrwFqsIfaHDNs0BTH',
    'description' => 'maeva2000@inwind.it - acct_1OhbRBPCrwFqsIfa',
    'period_month' => '2026-01',
    'raw_data' => 
    array (
      'id' => 'fee_1SlWhmPCrwFqsIfaNvNzCtWM',
      'object' => 'application_fee',
      'account' => 'acct_1OhbRBPCrwFqsIfa',
      'amount' => 306,
      'amount_refunded' => 0,
      'application' => 'ca_Ox2Rdo90ojLoUICobfcdmeRJeF2Icbzs',
      'balance_transaction' => 'txn_1SlWhoAns9lY52GQo6hMKxW1',
      'charge' => 'py_1SlWhlPCrwFqsIfaHDNs0BTH',
      'created' => 1767453926,
      'currency' => 'eur',
      'fee_source' => 
      array (
        'charge' => 'py_1SlWhlPCrwFqsIfaHDNs0BTH',
        'type' => 'charge',
      ),
      'livemode' => true,
      'originating_transaction' => 'ch_3SlWWxAns9lY52GQ0eljIcFJ',
      'refunded' => false,
      'refunds' => 
      array (
        'object' => 'list',
        'data' => 
        array (
        ),
        'has_more' => false,
        'total_count' => 0,
        'url' => '/v1/application_fees/fee_1SlWhmPCrwFqsIfaNvNzCtWM/refunds',
      ),
    ),
    'created_at' => '2026-01-07 19:12:48',
    'updated_at' => '2026-01-07 20:47:44',
  ),
  20 => 
  array (
    'id' => 21,
    'stripe_fee_id' => 'fee_1SlNoQPCrwFqsIfa35zRzZ9l',
    'amount' => '2.94',
    'currency' => 'EUR',
    'created_at_stripe' => '2026-01-07 21:47:44',
    'stripe_account_id' => 'acct_1OhbRBPCrwFqsIfa',
    'partner_email' => 'maeva2000@inwind.it',
    'partner_name' => 'SPEEDY PIZZA DI MASSIMILIANO SILVESTRI',
    'client_id' => 358,
    'charge_id' => 'py_1SlNoQPCrwFqsIfa8qrmu1Lp',
    'description' => 'maeva2000@inwind.it - acct_1OhbRBPCrwFqsIfa',
    'period_month' => '2026-01',
    'raw_data' => 
    array (
      'id' => 'fee_1SlNoQPCrwFqsIfa35zRzZ9l',
      'object' => 'application_fee',
      'account' => 'acct_1OhbRBPCrwFqsIfa',
      'amount' => 294,
      'amount_refunded' => 0,
      'application' => 'ca_Ox2Rdo90ojLoUICobfcdmeRJeF2Icbzs',
      'balance_transaction' => 'txn_1SlNoTAns9lY52GQHpGfx03X',
      'charge' => 'py_1SlNoQPCrwFqsIfa8qrmu1Lp',
      'created' => 1767419742,
      'currency' => 'eur',
      'fee_source' => 
      array (
        'charge' => 'py_1SlNoQPCrwFqsIfa8qrmu1Lp',
        'type' => 'charge',
      ),
      'livemode' => true,
      'originating_transaction' => 'ch_3SlNaYAns9lY52GQ1eHaA8y4',
      'refunded' => false,
      'refunds' => 
      array (
        'object' => 'list',
        'data' => 
        array (
        ),
        'has_more' => false,
        'total_count' => 0,
        'url' => '/v1/application_fees/fee_1SlNoQPCrwFqsIfa35zRzZ9l/refunds',
      ),
    ),
    'created_at' => '2026-01-07 19:12:49',
    'updated_at' => '2026-01-07 20:47:44',
  ),
  21 => 
  array (
    'id' => 22,
    'stripe_fee_id' => 'fee_1SlCcHPIzlXORG3awI1wgrbA',
    'amount' => '5.09',
    'currency' => 'EUR',
    'created_at_stripe' => '2026-01-07 21:47:44',
    'stripe_account_id' => 'acct_1OYpemPIzlXORG3a',
    'partner_email' => 'feusrl.2019@gmail.com',
    'partner_name' => 'Feu Srl',
    'client_id' => 301,
    'charge_id' => 'py_1SlCcHPIzlXORG3aMR2Q0sv1',
    'description' => 'feusrl.2019@gmail.com - acct_1OYpemPIzlXORG3a',
    'period_month' => '2026-01',
    'raw_data' => 
    array (
      'id' => 'fee_1SlCcHPIzlXORG3awI1wgrbA',
      'object' => 'application_fee',
      'account' => 'acct_1OYpemPIzlXORG3a',
      'amount' => 509,
      'amount_refunded' => 0,
      'application' => 'ca_Ox2Rdo90ojLoUICobfcdmeRJeF2Icbzs',
      'balance_transaction' => 'txn_1SlCcKAns9lY52GQzeFT6vPl',
      'charge' => 'py_1SlCcHPIzlXORG3aMR2Q0sv1',
      'created' => 1767376705,
      'currency' => 'eur',
      'fee_source' => 
      array (
        'charge' => 'py_1SlCcHPIzlXORG3aMR2Q0sv1',
        'type' => 'charge',
      ),
      'livemode' => true,
      'originating_transaction' => 'ch_3SlBSpAns9lY52GQ0l5nmZYw',
      'refunded' => false,
      'refunds' => 
      array (
        'object' => 'list',
        'data' => 
        array (
        ),
        'has_more' => false,
        'total_count' => 0,
        'url' => '/v1/application_fees/fee_1SlCcHPIzlXORG3awI1wgrbA/refunds',
      ),
    ),
    'created_at' => '2026-01-07 19:12:50',
    'updated_at' => '2026-01-07 20:47:44',
  ),
  22 => 
  array (
    'id' => 23,
    'stripe_fee_id' => 'fee_1SlAOmPEpkzElSu4SxlAMvGJ',
    'amount' => '4.23',
    'currency' => 'EUR',
    'created_at_stripe' => '2026-01-07 21:47:44',
    'stripe_account_id' => 'acct_1OrHzGPEpkzElSu4',
    'partner_email' => 'andreadellomodarme@gmail.com',
    'partner_name' => 'PUNTO P DI ANDREA DELL\'OMODARME',
    'client_id' => 383,
    'charge_id' => 'py_1SlAOmPEpkzElSu4FNcoz4uf',
    'description' => 'andreadellomodarme@gmail.com - acct_1OrHzGPEpkzElSu4',
    'period_month' => '2026-01',
    'raw_data' => 
    array (
      'id' => 'fee_1SlAOmPEpkzElSu4SxlAMvGJ',
      'object' => 'application_fee',
      'account' => 'acct_1OrHzGPEpkzElSu4',
      'amount' => 423,
      'amount_refunded' => 0,
      'application' => 'ca_Ox2Rdo90ojLoUICobfcdmeRJeF2Icbzs',
      'balance_transaction' => 'txn_1SlAOpAns9lY52GQ6yGpJwtq',
      'charge' => 'py_1SlAOmPEpkzElSu4FNcoz4uf',
      'created' => 1767368180,
      'currency' => 'eur',
      'fee_source' => 
      array (
        'charge' => 'py_1SlAOmPEpkzElSu4FNcoz4uf',
        'type' => 'charge',
      ),
      'livemode' => true,
      'originating_transaction' => 'ch_3Sl94uAns9lY52GQ0KZSeLB8',
      'refunded' => false,
      'refunds' => 
      array (
        'object' => 'list',
        'data' => 
        array (
        ),
        'has_more' => false,
        'total_count' => 0,
        'url' => '/v1/application_fees/fee_1SlAOmPEpkzElSu4SxlAMvGJ/refunds',
      ),
    ),
    'created_at' => '2026-01-07 19:12:50',
    'updated_at' => '2026-01-07 20:47:44',
  ),
  23 => 
  array (
    'id' => 24,
    'stripe_fee_id' => 'fee_1SkrHoPIzlXORG3aQJsYrFC1',
    'amount' => '5.35',
    'currency' => 'EUR',
    'created_at_stripe' => '2026-01-07 21:47:44',
    'stripe_account_id' => 'acct_1OYpemPIzlXORG3a',
    'partner_email' => 'feusrl.2019@gmail.com',
    'partner_name' => 'Feu Srl',
    'client_id' => 301,
    'charge_id' => 'py_1SkrHoPIzlXORG3aTr9WYIGl',
    'description' => 'feusrl.2019@gmail.com - acct_1OYpemPIzlXORG3a',
    'period_month' => '2026-01',
    'raw_data' => 
    array (
      'id' => 'fee_1SkrHoPIzlXORG3aQJsYrFC1',
      'object' => 'application_fee',
      'account' => 'acct_1OYpemPIzlXORG3a',
      'amount' => 535,
      'amount_refunded' => 0,
      'application' => 'ca_Ox2Rdo90ojLoUICobfcdmeRJeF2Icbzs',
      'balance_transaction' => 'txn_1SkrHrAns9lY52GQzXNnDoJa',
      'charge' => 'py_1SkrHoPIzlXORG3aTr9WYIGl',
      'created' => 1767294712,
      'currency' => 'eur',
      'fee_source' => 
      array (
        'charge' => 'py_1SkrHoPIzlXORG3aTr9WYIGl',
        'type' => 'charge',
      ),
      'livemode' => true,
      'originating_transaction' => 'ch_3SkrC3Ans9lY52GQ1QEk3lnY',
      'refunded' => false,
      'refunds' => 
      array (
        'object' => 'list',
        'data' => 
        array (
        ),
        'has_more' => false,
        'total_count' => 0,
        'url' => '/v1/application_fees/fee_1SkrHoPIzlXORG3aQJsYrFC1/refunds',
      ),
    ),
    'created_at' => '2026-01-07 19:12:51',
    'updated_at' => '2026-01-07 20:47:44',
  ),
  24 => 
  array (
    'id' => 25,
    'stripe_fee_id' => 'fee_1Skqy9PIzlXORG3agubyZcy4',
    'amount' => '4.66',
    'currency' => 'EUR',
    'created_at_stripe' => '2026-01-07 21:47:44',
    'stripe_account_id' => 'acct_1OYpemPIzlXORG3a',
    'partner_email' => 'feusrl.2019@gmail.com',
    'partner_name' => 'Feu Srl',
    'client_id' => 301,
    'charge_id' => 'py_1Skqy9PIzlXORG3aMEnnrg7C',
    'description' => 'feusrl.2019@gmail.com - acct_1OYpemPIzlXORG3a',
    'period_month' => '2026-01',
    'raw_data' => 
    array (
      'id' => 'fee_1Skqy9PIzlXORG3agubyZcy4',
      'object' => 'application_fee',
      'account' => 'acct_1OYpemPIzlXORG3a',
      'amount' => 466,
      'amount_refunded' => 0,
      'application' => 'ca_Ox2Rdo90ojLoUICobfcdmeRJeF2Icbzs',
      'balance_transaction' => 'txn_1SkqyBAns9lY52GQapN94vsX',
      'charge' => 'py_1Skqy9PIzlXORG3aMEnnrg7C',
      'created' => 1767293493,
      'currency' => 'eur',
      'fee_source' => 
      array (
        'charge' => 'py_1Skqy9PIzlXORG3aMEnnrg7C',
        'type' => 'charge',
      ),
      'livemode' => true,
      'originating_transaction' => 'ch_3SkqugAns9lY52GQ1XJyYQRL',
      'refunded' => false,
      'refunds' => 
      array (
        'object' => 'list',
        'data' => 
        array (
        ),
        'has_more' => false,
        'total_count' => 0,
        'url' => '/v1/application_fees/fee_1Skqy9PIzlXORG3agubyZcy4/refunds',
      ),
    ),
    'created_at' => '2026-01-07 19:12:51',
    'updated_at' => '2026-01-07 20:47:44',
  ),
  25 => 
  array (
    'id' => 26,
    'stripe_fee_id' => 'fee_1SkqcqPIzlXORG3aykbde773',
    'amount' => '5.50',
    'currency' => 'EUR',
    'created_at_stripe' => '2026-01-07 21:47:44',
    'stripe_account_id' => 'acct_1OYpemPIzlXORG3a',
    'partner_email' => 'feusrl.2019@gmail.com',
    'partner_name' => 'Feu Srl',
    'client_id' => 301,
    'charge_id' => 'py_1SkqcpPIzlXORG3aFcppH8w2',
    'description' => 'feusrl.2019@gmail.com - acct_1OYpemPIzlXORG3a',
    'period_month' => '2026-01',
    'raw_data' => 
    array (
      'id' => 'fee_1SkqcqPIzlXORG3aykbde773',
      'object' => 'application_fee',
      'account' => 'acct_1OYpemPIzlXORG3a',
      'amount' => 550,
      'amount_refunded' => 0,
      'application' => 'ca_Ox2Rdo90ojLoUICobfcdmeRJeF2Icbzs',
      'balance_transaction' => 'txn_1SkqcsAns9lY52GQ0wpUqeIc',
      'charge' => 'py_1SkqcpPIzlXORG3aFcppH8w2',
      'created' => 1767292172,
      'currency' => 'eur',
      'fee_source' => 
      array (
        'charge' => 'py_1SkqcpPIzlXORG3aFcppH8w2',
        'type' => 'charge',
      ),
      'livemode' => true,
      'originating_transaction' => 'ch_3SkqbjAns9lY52GQ1ganQHQB',
      'refunded' => false,
      'refunds' => 
      array (
        'object' => 'list',
        'data' => 
        array (
        ),
        'has_more' => false,
        'total_count' => 0,
        'url' => '/v1/application_fees/fee_1SkqcqPIzlXORG3aykbde773/refunds',
      ),
    ),
    'created_at' => '2026-01-07 19:12:52',
    'updated_at' => '2026-01-07 20:47:44',
  ),
  26 => 
  array (
    'id' => 27,
    'stripe_fee_id' => 'fee_1Skpq2PIzlXORG3adOIKfglC',
    'amount' => '5.11',
    'currency' => 'EUR',
    'created_at_stripe' => '2026-01-07 21:47:44',
    'stripe_account_id' => 'acct_1OYpemPIzlXORG3a',
    'partner_email' => 'feusrl.2019@gmail.com',
    'partner_name' => 'Feu Srl',
    'client_id' => 301,
    'charge_id' => 'py_1Skpq2PIzlXORG3afNMARy4y',
    'description' => 'feusrl.2019@gmail.com - acct_1OYpemPIzlXORG3a',
    'period_month' => '2026-01',
    'raw_data' => 
    array (
      'id' => 'fee_1Skpq2PIzlXORG3adOIKfglC',
      'object' => 'application_fee',
      'account' => 'acct_1OYpemPIzlXORG3a',
      'amount' => 511,
      'amount_refunded' => 0,
      'application' => 'ca_Ox2Rdo90ojLoUICobfcdmeRJeF2Icbzs',
      'balance_transaction' => 'txn_1Skpq4Ans9lY52GQ9LnnIzFX',
      'charge' => 'py_1Skpq2PIzlXORG3afNMARy4y',
      'created' => 1767289146,
      'currency' => 'eur',
      'fee_source' => 
      array (
        'charge' => 'py_1Skpq2PIzlXORG3afNMARy4y',
        'type' => 'charge',
      ),
      'livemode' => true,
      'originating_transaction' => 'ch_3Sko44Ans9lY52GQ0RUYLOdW',
      'refunded' => false,
      'refunds' => 
      array (
        'object' => 'list',
        'data' => 
        array (
        ),
        'has_more' => false,
        'total_count' => 0,
        'url' => '/v1/application_fees/fee_1Skpq2PIzlXORG3adOIKfglC/refunds',
      ),
    ),
    'created_at' => '2026-01-07 19:12:53',
    'updated_at' => '2026-01-07 20:47:44',
  ),
  27 => 
  array (
    'id' => 28,
    'stripe_fee_id' => 'fee_1SkppxPEpkzElSu4JnkE3VVz',
    'amount' => '3.19',
    'currency' => 'EUR',
    'created_at_stripe' => '2026-01-07 21:47:44',
    'stripe_account_id' => 'acct_1OrHzGPEpkzElSu4',
    'partner_email' => 'andreadellomodarme@gmail.com',
    'partner_name' => 'PUNTO P DI ANDREA DELL\'OMODARME',
    'client_id' => 383,
    'charge_id' => 'py_1SkppxPEpkzElSu4tUdlxNAw',
    'description' => 'andreadellomodarme@gmail.com - acct_1OrHzGPEpkzElSu4',
    'period_month' => '2026-01',
    'raw_data' => 
    array (
      'id' => 'fee_1SkppxPEpkzElSu4JnkE3VVz',
      'object' => 'application_fee',
      'account' => 'acct_1OrHzGPEpkzElSu4',
      'amount' => 319,
      'amount_refunded' => 0,
      'application' => 'ca_Ox2Rdo90ojLoUICobfcdmeRJeF2Icbzs',
      'balance_transaction' => 'txn_1SkppzAns9lY52GQWqdxF47C',
      'charge' => 'py_1SkppxPEpkzElSu4tUdlxNAw',
      'created' => 1767289141,
      'currency' => 'eur',
      'fee_source' => 
      array (
        'charge' => 'py_1SkppxPEpkzElSu4tUdlxNAw',
        'type' => 'charge',
      ),
      'livemode' => true,
      'originating_transaction' => 'ch_3SkpohAns9lY52GQ0MOVxcS2',
      'refunded' => false,
      'refunds' => 
      array (
        'object' => 'list',
        'data' => 
        array (
        ),
        'has_more' => false,
        'total_count' => 0,
        'url' => '/v1/application_fees/fee_1SkppxPEpkzElSu4JnkE3VVz/refunds',
      ),
    ),
    'created_at' => '2026-01-07 19:12:53',
    'updated_at' => '2026-01-07 20:47:44',
  ),
  28 => 
  array (
    'id' => 29,
    'stripe_fee_id' => 'fee_1SkpU2PB8fwsTso32nMnNt5j',
    'amount' => '2.76',
    'currency' => 'EUR',
    'created_at_stripe' => '2026-01-07 21:47:44',
    'stripe_account_id' => 'acct_1Oe1NCPB8fwsTso3',
    'partner_email' => 'alepizza2000@gmail.com',
    'partner_name' => 'PIZZA E TORTA DA ALEPIZZA DI BIANCHI ALESSIO',
    'client_id' => 336,
    'charge_id' => 'py_1SkpU2PB8fwsTso3dm6ettU7',
    'description' => 'alepizza2000@gmail.com - acct_1Oe1NCPB8fwsTso3',
    'period_month' => '2026-01',
    'raw_data' => 
    array (
      'id' => 'fee_1SkpU2PB8fwsTso32nMnNt5j',
      'object' => 'application_fee',
      'account' => 'acct_1Oe1NCPB8fwsTso3',
      'amount' => 276,
      'amount_refunded' => 0,
      'application' => 'ca_Ox2Rdo90ojLoUICobfcdmeRJeF2Icbzs',
      'balance_transaction' => 'txn_1SkpU4Ans9lY52GQnUbfT16r',
      'charge' => 'py_1SkpU2PB8fwsTso3dm6ettU7',
      'created' => 1767287782,
      'currency' => 'eur',
      'fee_source' => 
      array (
        'charge' => 'py_1SkpU2PB8fwsTso3dm6ettU7',
        'type' => 'charge',
      ),
      'livemode' => true,
      'originating_transaction' => 'ch_3SkpTSAns9lY52GQ1XPOBZiV',
      'refunded' => false,
      'refunds' => 
      array (
        'object' => 'list',
        'data' => 
        array (
        ),
        'has_more' => false,
        'total_count' => 0,
        'url' => '/v1/application_fees/fee_1SkpU2PB8fwsTso32nMnNt5j/refunds',
      ),
    ),
    'created_at' => '2026-01-07 19:12:54',
    'updated_at' => '2026-01-07 20:47:44',
  ),
  29 => 
  array (
    'id' => 30,
    'stripe_fee_id' => 'fee_1SkoZjPEpkzElSu4NMP4SMzX',
    'amount' => '3.76',
    'currency' => 'EUR',
    'created_at_stripe' => '2026-01-07 21:47:44',
    'stripe_account_id' => 'acct_1OrHzGPEpkzElSu4',
    'partner_email' => 'andreadellomodarme@gmail.com',
    'partner_name' => 'PUNTO P DI ANDREA DELL\'OMODARME',
    'client_id' => 383,
    'charge_id' => 'py_1SkoZiPEpkzElSu4pSHDNI1m',
    'description' => 'andreadellomodarme@gmail.com - acct_1OrHzGPEpkzElSu4',
    'period_month' => '2026-01',
    'raw_data' => 
    array (
      'id' => 'fee_1SkoZjPEpkzElSu4NMP4SMzX',
      'object' => 'application_fee',
      'account' => 'acct_1OrHzGPEpkzElSu4',
      'amount' => 376,
      'amount_refunded' => 0,
      'application' => 'ca_Ox2Rdo90ojLoUICobfcdmeRJeF2Icbzs',
      'balance_transaction' => 'txn_1SkoZlAns9lY52GQkiprHHwd',
      'charge' => 'py_1SkoZiPEpkzElSu4pSHDNI1m',
      'created' => 1767284291,
      'currency' => 'eur',
      'fee_source' => 
      array (
        'charge' => 'py_1SkoZiPEpkzElSu4pSHDNI1m',
        'type' => 'charge',
      ),
      'livemode' => true,
      'originating_transaction' => 'ch_3SkoW9Ans9lY52GQ0BVImo0i',
      'refunded' => false,
      'refunds' => 
      array (
        'object' => 'list',
        'data' => 
        array (
        ),
        'has_more' => false,
        'total_count' => 0,
        'url' => '/v1/application_fees/fee_1SkoZjPEpkzElSu4NMP4SMzX/refunds',
      ),
    ),
    'created_at' => '2026-01-07 19:12:55',
    'updated_at' => '2026-01-07 20:47:44',
  ),
  30 => 
  array (
    'id' => 31,
    'stripe_fee_id' => 'fee_1SknwzPEpkzElSu44OK68EOm',
    'amount' => '3.42',
    'currency' => 'EUR',
    'created_at_stripe' => '2026-01-07 21:47:44',
    'stripe_account_id' => 'acct_1OrHzGPEpkzElSu4',
    'partner_email' => 'andreadellomodarme@gmail.com',
    'partner_name' => 'PUNTO P DI ANDREA DELL\'OMODARME',
    'client_id' => 383,
    'charge_id' => 'py_1SknwzPEpkzElSu4QMK1kzz7',
    'description' => 'andreadellomodarme@gmail.com - acct_1OrHzGPEpkzElSu4',
    'period_month' => '2026-01',
    'raw_data' => 
    array (
      'id' => 'fee_1SknwzPEpkzElSu44OK68EOm',
      'object' => 'application_fee',
      'account' => 'acct_1OrHzGPEpkzElSu4',
      'amount' => 342,
      'amount_refunded' => 0,
      'application' => 'ca_Ox2Rdo90ojLoUICobfcdmeRJeF2Icbzs',
      'balance_transaction' => 'txn_1Sknx1Ans9lY52GQjp3UoqbK',
      'charge' => 'py_1SknwzPEpkzElSu4QMK1kzz7',
      'created' => 1767281889,
      'currency' => 'eur',
      'fee_source' => 
      array (
        'charge' => 'py_1SknwzPEpkzElSu4QMK1kzz7',
        'type' => 'charge',
      ),
      'livemode' => true,
      'originating_transaction' => 'ch_3SknBkAns9lY52GQ17vAZaIz',
      'refunded' => false,
      'refunds' => 
      array (
        'object' => 'list',
        'data' => 
        array (
        ),
        'has_more' => false,
        'total_count' => 0,
        'url' => '/v1/application_fees/fee_1SknwzPEpkzElSu44OK68EOm/refunds',
      ),
    ),
    'created_at' => '2026-01-07 19:12:55',
    'updated_at' => '2026-01-07 20:47:44',
  ),
  31 => 
  array (
    'id' => 32,
    'stripe_fee_id' => 'fee_1SknwCPEpkzElSu4fMHSMC0x',
    'amount' => '2.94',
    'currency' => 'EUR',
    'created_at_stripe' => '2026-01-07 21:47:44',
    'stripe_account_id' => 'acct_1OrHzGPEpkzElSu4',
    'partner_email' => 'andreadellomodarme@gmail.com',
    'partner_name' => 'PUNTO P DI ANDREA DELL\'OMODARME',
    'client_id' => 383,
    'charge_id' => 'py_1SknwCPEpkzElSu4tUeltrFK',
    'description' => 'andreadellomodarme@gmail.com - acct_1OrHzGPEpkzElSu4',
    'period_month' => '2026-01',
    'raw_data' => 
    array (
      'id' => 'fee_1SknwCPEpkzElSu4fMHSMC0x',
      'object' => 'application_fee',
      'account' => 'acct_1OrHzGPEpkzElSu4',
      'amount' => 294,
      'amount_refunded' => 0,
      'application' => 'ca_Ox2Rdo90ojLoUICobfcdmeRJeF2Icbzs',
      'balance_transaction' => 'txn_1SknwEAns9lY52GQfru0S3b5',
      'charge' => 'py_1SknwCPEpkzElSu4tUeltrFK',
      'created' => 1767281840,
      'currency' => 'eur',
      'fee_source' => 
      array (
        'charge' => 'py_1SknwCPEpkzElSu4tUeltrFK',
        'type' => 'charge',
      ),
      'livemode' => true,
      'originating_transaction' => 'ch_3SkmJaAns9lY52GQ0ZoocIm7',
      'refunded' => false,
      'refunds' => 
      array (
        'object' => 'list',
        'data' => 
        array (
        ),
        'has_more' => false,
        'total_count' => 0,
        'url' => '/v1/application_fees/fee_1SknwCPEpkzElSu4fMHSMC0x/refunds',
      ),
    ),
    'created_at' => '2026-01-07 19:12:56',
    'updated_at' => '2026-01-07 20:47:44',
  ),
  32 => 
  array (
    'id' => 33,
    'stripe_fee_id' => 'fee_1Sk7zRPAESt8veHwIj8wQJcF',
    'amount' => '2.92',
    'currency' => 'EUR',
    'created_at_stripe' => '2026-01-07 21:47:44',
    'stripe_account_id' => 'acct_1ROCdFPAESt8veHw',
    'partner_email' => 'anticatradizione1950@gmail.com',
    'partner_name' => 'Osteria Antica Tradizione srls.',
    'client_id' => 332,
    'charge_id' => 'py_1Sk7zRPAESt8veHwEmojogcc',
    'description' => 'anticatradizione1950@gmail.com - acct_1ROCdFPAESt8veHw',
    'period_month' => '2025-12',
    'raw_data' => 
    array (
      'id' => 'fee_1Sk7zRPAESt8veHwIj8wQJcF',
      'object' => 'application_fee',
      'account' => 'acct_1ROCdFPAESt8veHw',
      'amount' => 292,
      'amount_refunded' => 0,
      'application' => 'ca_Ox2Rdo90ojLoUICobfcdmeRJeF2Icbzs',
      'balance_transaction' => 'txn_1Sk7zTAns9lY52GQyoOLSrD2',
      'charge' => 'py_1Sk7zRPAESt8veHwEmojogcc',
      'created' => 1767120593,
      'currency' => 'eur',
      'fee_source' => 
      array (
        'charge' => 'py_1Sk7zRPAESt8veHwEmojogcc',
        'type' => 'charge',
      ),
      'livemode' => true,
      'originating_transaction' => 'ch_3Sk7z2Ans9lY52GQ1iZBBYTm',
      'refunded' => false,
      'refunds' => 
      array (
        'object' => 'list',
        'data' => 
        array (
        ),
        'has_more' => false,
        'total_count' => 0,
        'url' => '/v1/application_fees/fee_1Sk7zRPAESt8veHwIj8wQJcF/refunds',
      ),
    ),
    'created_at' => '2026-01-07 19:12:56',
    'updated_at' => '2026-01-07 20:47:44',
  ),
  33 => 
  array (
    'id' => 34,
    'stripe_fee_id' => 'fee_1SjlRxPCrwFqsIfanmUs8l6q',
    'amount' => '3.07',
    'currency' => 'EUR',
    'created_at_stripe' => '2026-01-07 21:47:44',
    'stripe_account_id' => 'acct_1OhbRBPCrwFqsIfa',
    'partner_email' => 'maeva2000@inwind.it',
    'partner_name' => 'SPEEDY PIZZA DI MASSIMILIANO SILVESTRI',
    'client_id' => 358,
    'charge_id' => 'py_1SjlRxPCrwFqsIfaC0TYMkKW',
    'description' => 'maeva2000@inwind.it - acct_1OhbRBPCrwFqsIfa',
    'period_month' => '2025-12',
    'raw_data' => 
    array (
      'id' => 'fee_1SjlRxPCrwFqsIfanmUs8l6q',
      'object' => 'application_fee',
      'account' => 'acct_1OhbRBPCrwFqsIfa',
      'amount' => 307,
      'amount_refunded' => 0,
      'application' => 'ca_Ox2Rdo90ojLoUICobfcdmeRJeF2Icbzs',
      'balance_transaction' => 'txn_1SjlRzAns9lY52GQVscbTIFV',
      'charge' => 'py_1SjlRxPCrwFqsIfaC0TYMkKW',
      'created' => 1767033949,
      'currency' => 'eur',
      'fee_source' => 
      array (
        'charge' => 'py_1SjlRxPCrwFqsIfaC0TYMkKW',
        'type' => 'charge',
      ),
      'livemode' => true,
      'originating_transaction' => 'ch_3SjilqAns9lY52GQ1Yv1IJBe',
      'refunded' => false,
      'refunds' => 
      array (
        'object' => 'list',
        'data' => 
        array (
        ),
        'has_more' => false,
        'total_count' => 0,
        'url' => '/v1/application_fees/fee_1SjlRxPCrwFqsIfanmUs8l6q/refunds',
      ),
    ),
    'created_at' => '2026-01-07 19:12:57',
    'updated_at' => '2026-01-07 20:47:44',
  ),
  34 => 
  array (
    'id' => 35,
    'stripe_fee_id' => 'fee_1SjkNcPB7qjhlfVaFAW6Li1c',
    'amount' => '3.14',
    'currency' => 'EUR',
    'created_at_stripe' => '2026-01-07 21:47:44',
    'stripe_account_id' => 'acct_1OrlPGPB7qjhlfVa',
    'partner_email' => 'laboratoriodellapizza@gmail.com',
    'partner_name' => 'Macrì Domenico',
    'client_id' => 325,
    'charge_id' => 'py_1SjkNcPB7qjhlfVaFuydhSZ4',
    'description' => 'laboratoriodellapizza@gmail.com - acct_1OrlPGPB7qjhlfVa',
    'period_month' => '2025-12',
    'raw_data' => 
    array (
      'id' => 'fee_1SjkNcPB7qjhlfVaFAW6Li1c',
      'object' => 'application_fee',
      'account' => 'acct_1OrlPGPB7qjhlfVa',
      'amount' => 314,
      'amount_refunded' => 0,
      'application' => 'ca_Ox2Rdo90ojLoUICobfcdmeRJeF2Icbzs',
      'balance_transaction' => 'txn_1SjkNeAns9lY52GQE2E90Ef5',
      'charge' => 'py_1SjkNcPB7qjhlfVaFuydhSZ4',
      'created' => 1767029836,
      'currency' => 'eur',
      'fee_source' => 
      array (
        'charge' => 'py_1SjkNcPB7qjhlfVaFuydhSZ4',
        'type' => 'charge',
      ),
      'livemode' => true,
      'originating_transaction' => 'ch_3SjkN2Ans9lY52GQ0PisOgxy',
      'refunded' => false,
      'refunds' => 
      array (
        'object' => 'list',
        'data' => 
        array (
        ),
        'has_more' => false,
        'total_count' => 0,
        'url' => '/v1/application_fees/fee_1SjkNcPB7qjhlfVaFAW6Li1c/refunds',
      ),
    ),
    'created_at' => '2026-01-07 19:12:58',
    'updated_at' => '2026-01-07 20:47:44',
  ),
  35 => 
  array (
    'id' => 36,
    'stripe_fee_id' => 'fee_1SjNyOPIzlXORG3a6Jdt6M2J',
    'amount' => '4.99',
    'currency' => 'EUR',
    'created_at_stripe' => '2026-01-07 21:47:44',
    'stripe_account_id' => 'acct_1OYpemPIzlXORG3a',
    'partner_email' => 'feusrl.2019@gmail.com',
    'partner_name' => 'Feu Srl',
    'client_id' => 301,
    'charge_id' => 'py_1SjNyOPIzlXORG3aRkIJZqHA',
    'description' => 'feusrl.2019@gmail.com - acct_1OYpemPIzlXORG3a',
    'period_month' => '2025-12',
    'raw_data' => 
    array (
      'id' => 'fee_1SjNyOPIzlXORG3a6Jdt6M2J',
      'object' => 'application_fee',
      'account' => 'acct_1OYpemPIzlXORG3a',
      'amount' => 499,
      'amount_refunded' => 0,
      'application' => 'ca_Ox2Rdo90ojLoUICobfcdmeRJeF2Icbzs',
      'balance_transaction' => 'txn_1SjNyRAns9lY52GQGY3UMygv',
      'charge' => 'py_1SjNyOPIzlXORG3aRkIJZqHA',
      'created' => 1766943704,
      'currency' => 'eur',
      'fee_source' => 
      array (
        'charge' => 'py_1SjNyOPIzlXORG3aRkIJZqHA',
        'type' => 'charge',
      ),
      'livemode' => true,
      'originating_transaction' => 'ch_3SjNrvAns9lY52GQ1MmuspDU',
      'refunded' => false,
      'refunds' => 
      array (
        'object' => 'list',
        'data' => 
        array (
        ),
        'has_more' => false,
        'total_count' => 0,
        'url' => '/v1/application_fees/fee_1SjNyOPIzlXORG3a6Jdt6M2J/refunds',
      ),
    ),
    'created_at' => '2026-01-07 19:12:58',
    'updated_at' => '2026-01-07 20:47:44',
  ),
  36 => 
  array (
    'id' => 37,
    'stripe_fee_id' => 'fee_1SjNBFAcaExTZKe82ZbnrHTa',
    'amount' => '3.90',
    'currency' => 'EUR',
    'created_at_stripe' => '2026-01-07 21:47:44',
    'stripe_account_id' => 'acct_1QsMHWAcaExTZKe8',
    'partner_email' => 'nclcucc@gmail.com',
    'partner_name' => 'La Loggia sul Mare SRL',
    'client_id' => 322,
    'charge_id' => 'py_1SjNBFAcaExTZKe8IJoSBmSY',
    'description' => 'nclcucc@gmail.com - acct_1QsMHWAcaExTZKe8',
    'period_month' => '2025-12',
    'raw_data' => 
    array (
      'id' => 'fee_1SjNBFAcaExTZKe82ZbnrHTa',
      'object' => 'application_fee',
      'account' => 'acct_1QsMHWAcaExTZKe8',
      'amount' => 390,
      'amount_refunded' => 0,
      'application' => 'ca_Ox2Rdo90ojLoUICobfcdmeRJeF2Icbzs',
      'balance_transaction' => 'txn_1SjNBHAns9lY52GQvait2yw2',
      'charge' => 'py_1SjNBFAcaExTZKe8IJoSBmSY',
      'created' => 1766940657,
      'currency' => 'eur',
      'fee_source' => 
      array (
        'charge' => 'py_1SjNBFAcaExTZKe8IJoSBmSY',
        'type' => 'charge',
      ),
      'livemode' => true,
      'originating_transaction' => 'ch_3SjMSJAns9lY52GQ1g4vSadA',
      'refunded' => false,
      'refunds' => 
      array (
        'object' => 'list',
        'data' => 
        array (
        ),
        'has_more' => false,
        'total_count' => 0,
        'url' => '/v1/application_fees/fee_1SjNBFAcaExTZKe82ZbnrHTa/refunds',
      ),
    ),
    'created_at' => '2026-01-07 19:12:59',
    'updated_at' => '2026-01-07 20:47:44',
  ),
  37 => 
  array (
    'id' => 38,
    'stripe_fee_id' => 'fee_1SjLFNPEpkzElSu4UtKrMSwk',
    'amount' => '2.95',
    'currency' => 'EUR',
    'created_at_stripe' => '2026-01-07 21:47:44',
    'stripe_account_id' => 'acct_1OrHzGPEpkzElSu4',
    'partner_email' => 'andreadellomodarme@gmail.com',
    'partner_name' => 'PUNTO P DI ANDREA DELL\'OMODARME',
    'client_id' => 383,
    'charge_id' => 'py_1SjLFMPEpkzElSu4bwjXdcKQ',
    'description' => 'andreadellomodarme@gmail.com - acct_1OrHzGPEpkzElSu4',
    'period_month' => '2025-12',
    'raw_data' => 
    array (
      'id' => 'fee_1SjLFNPEpkzElSu4UtKrMSwk',
      'object' => 'application_fee',
      'account' => 'acct_1OrHzGPEpkzElSu4',
      'amount' => 295,
      'amount_refunded' => 0,
      'application' => 'ca_Ox2Rdo90ojLoUICobfcdmeRJeF2Icbzs',
      'balance_transaction' => 'txn_1SjLFPAns9lY52GQb9dxtqkE',
      'charge' => 'py_1SjLFMPEpkzElSu4bwjXdcKQ',
      'created' => 1766933225,
      'currency' => 'eur',
      'fee_source' => 
      array (
        'charge' => 'py_1SjLFMPEpkzElSu4bwjXdcKQ',
        'type' => 'charge',
      ),
      'livemode' => true,
      'originating_transaction' => 'ch_3SjKf0Ans9lY52GQ1OeDXIXm',
      'refunded' => false,
      'refunds' => 
      array (
        'object' => 'list',
        'data' => 
        array (
        ),
        'has_more' => false,
        'total_count' => 0,
        'url' => '/v1/application_fees/fee_1SjLFNPEpkzElSu4UtKrMSwk/refunds',
      ),
    ),
    'created_at' => '2026-01-07 19:12:59',
    'updated_at' => '2026-01-07 20:47:44',
  ),
  38 => 
  array (
    'id' => 39,
    'stripe_fee_id' => 'fee_1SjJPMPIzlXORG3aZcpWLZON',
    'amount' => '4.99',
    'currency' => 'EUR',
    'created_at_stripe' => '2026-01-07 21:47:44',
    'stripe_account_id' => 'acct_1OYpemPIzlXORG3a',
    'partner_email' => 'feusrl.2019@gmail.com',
    'partner_name' => 'Feu Srl',
    'client_id' => 301,
    'charge_id' => 'py_1SjJPLPIzlXORG3aX981rfdI',
    'description' => 'feusrl.2019@gmail.com - acct_1OYpemPIzlXORG3a',
    'period_month' => '2025-12',
    'raw_data' => 
    array (
      'id' => 'fee_1SjJPMPIzlXORG3aZcpWLZON',
      'object' => 'application_fee',
      'account' => 'acct_1OYpemPIzlXORG3a',
      'amount' => 499,
      'amount_refunded' => 0,
      'application' => 'ca_Ox2Rdo90ojLoUICobfcdmeRJeF2Icbzs',
      'balance_transaction' => 'txn_1SjJPOAns9lY52GQmhotzJK3',
      'charge' => 'py_1SjJPLPIzlXORG3aX981rfdI',
      'created' => 1766926156,
      'currency' => 'eur',
      'fee_source' => 
      array (
        'charge' => 'py_1SjJPLPIzlXORG3aX981rfdI',
        'type' => 'charge',
      ),
      'livemode' => true,
      'originating_transaction' => 'ch_3SjIzcAns9lY52GQ1PANhi5A',
      'refunded' => false,
      'refunds' => 
      array (
        'object' => 'list',
        'data' => 
        array (
        ),
        'has_more' => false,
        'total_count' => 0,
        'url' => '/v1/application_fees/fee_1SjJPMPIzlXORG3aZcpWLZON/refunds',
      ),
    ),
    'created_at' => '2026-01-07 19:13:00',
    'updated_at' => '2026-01-07 20:47:44',
  ),
  39 => 
  array (
    'id' => 40,
    'stripe_fee_id' => 'fee_1Sj2haPEpkzElSu4ciLiLMTQ',
    'amount' => '3.23',
    'currency' => 'EUR',
    'created_at_stripe' => '2026-01-07 21:47:44',
    'stripe_account_id' => 'acct_1OrHzGPEpkzElSu4',
    'partner_email' => 'andreadellomodarme@gmail.com',
    'partner_name' => 'PUNTO P DI ANDREA DELL\'OMODARME',
    'client_id' => 383,
    'charge_id' => 'py_1Sj2haPEpkzElSu4fIMOZTbi',
    'description' => 'andreadellomodarme@gmail.com - acct_1OrHzGPEpkzElSu4',
    'period_month' => '2025-12',
    'raw_data' => 
    array (
      'id' => 'fee_1Sj2haPEpkzElSu4ciLiLMTQ',
      'object' => 'application_fee',
      'account' => 'acct_1OrHzGPEpkzElSu4',
      'amount' => 323,
      'amount_refunded' => 0,
      'application' => 'ca_Ox2Rdo90ojLoUICobfcdmeRJeF2Icbzs',
      'balance_transaction' => 'txn_1Sj2hdAns9lY52GQE2IZnNjL',
      'charge' => 'py_1Sj2haPEpkzElSu4fIMOZTbi',
      'created' => 1766861938,
      'currency' => 'eur',
      'fee_source' => 
      array (
        'charge' => 'py_1Sj2haPEpkzElSu4fIMOZTbi',
        'type' => 'charge',
      ),
      'livemode' => true,
      'originating_transaction' => 'ch_3Sj23eAns9lY52GQ0UHCpTRV',
      'refunded' => false,
      'refunds' => 
      array (
        'object' => 'list',
        'data' => 
        array (
        ),
        'has_more' => false,
        'total_count' => 0,
        'url' => '/v1/application_fees/fee_1Sj2haPEpkzElSu4ciLiLMTQ/refunds',
      ),
    ),
    'created_at' => '2026-01-07 19:13:01',
    'updated_at' => '2026-01-07 20:47:44',
  ),
  40 => 
  array (
    'id' => 41,
    'stripe_fee_id' => 'fee_1Sj1CGPIzlXORG3aU81rC1XZ',
    'amount' => '3.10',
    'currency' => 'EUR',
    'created_at_stripe' => '2026-01-07 21:47:44',
    'stripe_account_id' => 'acct_1OYpemPIzlXORG3a',
    'partner_email' => 'feusrl.2019@gmail.com',
    'partner_name' => 'Feu Srl',
    'client_id' => 301,
    'charge_id' => 'py_1Sj1CGPIzlXORG3a3kSi2ekr',
    'description' => 'feusrl.2019@gmail.com - acct_1OYpemPIzlXORG3a',
    'period_month' => '2025-12',
    'raw_data' => 
    array (
      'id' => 'fee_1Sj1CGPIzlXORG3aU81rC1XZ',
      'object' => 'application_fee',
      'account' => 'acct_1OYpemPIzlXORG3a',
      'amount' => 310,
      'amount_refunded' => 0,
      'application' => 'ca_Ox2Rdo90ojLoUICobfcdmeRJeF2Icbzs',
      'balance_transaction' => 'txn_1Sj1CJAns9lY52GQ2fwFnUKp',
      'charge' => 'py_1Sj1CGPIzlXORG3a3kSi2ekr',
      'created' => 1766856152,
      'currency' => 'eur',
      'fee_source' => 
      array (
        'charge' => 'py_1Sj1CGPIzlXORG3a3kSi2ekr',
        'type' => 'charge',
      ),
      'livemode' => true,
      'originating_transaction' => 'ch_3Sj1BnAns9lY52GQ0XDT5f7j',
      'refunded' => false,
      'refunds' => 
      array (
        'object' => 'list',
        'data' => 
        array (
        ),
        'has_more' => false,
        'total_count' => 0,
        'url' => '/v1/application_fees/fee_1Sj1CGPIzlXORG3aU81rC1XZ/refunds',
      ),
    ),
    'created_at' => '2026-01-07 19:13:01',
    'updated_at' => '2026-01-07 20:47:44',
  ),
  41 => 
  array (
    'id' => 42,
    'stripe_fee_id' => 'fee_1Sj19VPCrwFqsIfax3PGQfPR',
    'amount' => '3.05',
    'currency' => 'EUR',
    'created_at_stripe' => '2026-01-07 21:47:44',
    'stripe_account_id' => 'acct_1OhbRBPCrwFqsIfa',
    'partner_email' => 'maeva2000@inwind.it',
    'partner_name' => 'SPEEDY PIZZA DI MASSIMILIANO SILVESTRI',
    'client_id' => 358,
    'charge_id' => 'py_1Sj19VPCrwFqsIfaIUL4qrIQ',
    'description' => 'maeva2000@inwind.it - acct_1OhbRBPCrwFqsIfa',
    'period_month' => '2025-12',
    'raw_data' => 
    array (
      'id' => 'fee_1Sj19VPCrwFqsIfax3PGQfPR',
      'object' => 'application_fee',
      'account' => 'acct_1OhbRBPCrwFqsIfa',
      'amount' => 305,
      'amount_refunded' => 0,
      'application' => 'ca_Ox2Rdo90ojLoUICobfcdmeRJeF2Icbzs',
      'balance_transaction' => 'txn_1Sj19XAns9lY52GQEmx83RwK',
      'charge' => 'py_1Sj19VPCrwFqsIfaIUL4qrIQ',
      'created' => 1766855981,
      'currency' => 'eur',
      'fee_source' => 
      array (
        'charge' => 'py_1Sj19VPCrwFqsIfaIUL4qrIQ',
        'type' => 'charge',
      ),
      'livemode' => true,
      'originating_transaction' => 'ch_3Sj16pAns9lY52GQ18s3yoA6',
      'refunded' => false,
      'refunds' => 
      array (
        'object' => 'list',
        'data' => 
        array (
        ),
        'has_more' => false,
        'total_count' => 0,
        'url' => '/v1/application_fees/fee_1Sj19VPCrwFqsIfax3PGQfPR/refunds',
      ),
    ),
    'created_at' => '2026-01-07 19:13:02',
    'updated_at' => '2026-01-07 20:47:44',
  ),
  42 => 
  array (
    'id' => 43,
    'stripe_fee_id' => 'fee_1Sj0rfPIzlXORG3afdvCsVXy',
    'amount' => '3.40',
    'currency' => 'EUR',
    'created_at_stripe' => '2026-01-07 21:47:44',
    'stripe_account_id' => 'acct_1OYpemPIzlXORG3a',
    'partner_email' => 'feusrl.2019@gmail.com',
    'partner_name' => 'Feu Srl',
    'client_id' => 301,
    'charge_id' => 'py_1Sj0rfPIzlXORG3akJBJZYRI',
    'description' => 'feusrl.2019@gmail.com - acct_1OYpemPIzlXORG3a',
    'period_month' => '2025-12',
    'raw_data' => 
    array (
      'id' => 'fee_1Sj0rfPIzlXORG3afdvCsVXy',
      'object' => 'application_fee',
      'account' => 'acct_1OYpemPIzlXORG3a',
      'amount' => 340,
      'amount_refunded' => 0,
      'application' => 'ca_Ox2Rdo90ojLoUICobfcdmeRJeF2Icbzs',
      'balance_transaction' => 'txn_1Sj0rhAns9lY52GQAeFX8PWS',
      'charge' => 'py_1Sj0rfPIzlXORG3akJBJZYRI',
      'created' => 1766854875,
      'currency' => 'eur',
      'fee_source' => 
      array (
        'charge' => 'py_1Sj0rfPIzlXORG3akJBJZYRI',
        'type' => 'charge',
      ),
      'livemode' => true,
      'originating_transaction' => 'ch_3Sj0r1Ans9lY52GQ1f9RuXdp',
      'refunded' => false,
      'refunds' => 
      array (
        'object' => 'list',
        'data' => 
        array (
        ),
        'has_more' => false,
        'total_count' => 0,
        'url' => '/v1/application_fees/fee_1Sj0rfPIzlXORG3afdvCsVXy/refunds',
      ),
    ),
    'created_at' => '2026-01-07 19:13:02',
    'updated_at' => '2026-01-07 20:47:44',
  ),
  43 => 
  array (
    'id' => 44,
    'stripe_fee_id' => 'fee_1SizJ3PCrwFqsIfa1UCG4VMK',
    'amount' => '2.93',
    'currency' => 'EUR',
    'created_at_stripe' => '2026-01-07 21:47:44',
    'stripe_account_id' => 'acct_1OhbRBPCrwFqsIfa',
    'partner_email' => 'maeva2000@inwind.it',
    'partner_name' => 'SPEEDY PIZZA DI MASSIMILIANO SILVESTRI',
    'client_id' => 358,
    'charge_id' => 'py_1SizJ3PCrwFqsIfaNGFkCrAx',
    'description' => 'maeva2000@inwind.it - acct_1OhbRBPCrwFqsIfa',
    'period_month' => '2025-12',
    'raw_data' => 
    array (
      'id' => 'fee_1SizJ3PCrwFqsIfa1UCG4VMK',
      'object' => 'application_fee',
      'account' => 'acct_1OhbRBPCrwFqsIfa',
      'amount' => 293,
      'amount_refunded' => 0,
      'application' => 'ca_Ox2Rdo90ojLoUICobfcdmeRJeF2Icbzs',
      'balance_transaction' => 'txn_1SizJ5Ans9lY52GQEjEaz0Yo',
      'charge' => 'py_1SizJ3PCrwFqsIfaNGFkCrAx',
      'created' => 1766848885,
      'currency' => 'eur',
      'fee_source' => 
      array (
        'charge' => 'py_1SizJ3PCrwFqsIfaNGFkCrAx',
        'type' => 'charge',
      ),
      'livemode' => true,
      'originating_transaction' => 'ch_3SizEiAns9lY52GQ0aGza4iM',
      'refunded' => false,
      'refunds' => 
      array (
        'object' => 'list',
        'data' => 
        array (
        ),
        'has_more' => false,
        'total_count' => 0,
        'url' => '/v1/application_fees/fee_1SizJ3PCrwFqsIfa1UCG4VMK/refunds',
      ),
    ),
    'created_at' => '2026-01-07 19:13:03',
    'updated_at' => '2026-01-07 20:47:44',
  ),
  44 => 
  array (
    'id' => 45,
    'stripe_fee_id' => 'fee_1Shv8yAcaExTZKe867S918uy',
    'amount' => '3.89',
    'currency' => 'EUR',
    'created_at_stripe' => '2026-01-07 21:47:44',
    'stripe_account_id' => 'acct_1QsMHWAcaExTZKe8',
    'partner_email' => 'nclcucc@gmail.com',
    'partner_name' => 'La Loggia sul Mare SRL',
    'client_id' => 322,
    'charge_id' => 'py_1Shv8yAcaExTZKe8fwlnQkih',
    'description' => 'nclcucc@gmail.com - acct_1QsMHWAcaExTZKe8',
    'period_month' => '2025-12',
    'raw_data' => 
    array (
      'id' => 'fee_1Shv8yAcaExTZKe867S918uy',
      'object' => 'application_fee',
      'account' => 'acct_1QsMHWAcaExTZKe8',
      'amount' => 389,
      'amount_refunded' => 0,
      'application' => 'ca_Ox2Rdo90ojLoUICobfcdmeRJeF2Icbzs',
      'balance_transaction' => 'txn_1Shv91Ans9lY52GQ7RxEvqWO',
      'charge' => 'py_1Shv8yAcaExTZKe8fwlnQkih',
      'created' => 1766594556,
      'currency' => 'eur',
      'fee_source' => 
      array (
        'charge' => 'py_1Shv8yAcaExTZKe8fwlnQkih',
        'type' => 'charge',
      ),
      'livemode' => true,
      'originating_transaction' => 'ch_3ShpNAAns9lY52GQ07q9Bln0',
      'refunded' => false,
      'refunds' => 
      array (
        'object' => 'list',
        'data' => 
        array (
        ),
        'has_more' => false,
        'total_count' => 0,
        'url' => '/v1/application_fees/fee_1Shv8yAcaExTZKe867S918uy/refunds',
      ),
    ),
    'created_at' => '2026-01-07 19:13:04',
    'updated_at' => '2026-01-07 20:47:44',
  ),
  45 => 
  array (
    'id' => 46,
    'stripe_fee_id' => 'fee_1ShseKPEpkzElSu44aGupegw',
    'amount' => '3.06',
    'currency' => 'EUR',
    'created_at_stripe' => '2026-01-07 21:47:44',
    'stripe_account_id' => 'acct_1OrHzGPEpkzElSu4',
    'partner_email' => 'andreadellomodarme@gmail.com',
    'partner_name' => 'PUNTO P DI ANDREA DELL\'OMODARME',
    'client_id' => 383,
    'charge_id' => 'py_1ShseKPEpkzElSu4ZALo6Sfm',
    'description' => 'andreadellomodarme@gmail.com - acct_1OrHzGPEpkzElSu4',
    'period_month' => '2025-12',
    'raw_data' => 
    array (
      'id' => 'fee_1ShseKPEpkzElSu44aGupegw',
      'object' => 'application_fee',
      'account' => 'acct_1OrHzGPEpkzElSu4',
      'amount' => 306,
      'amount_refunded' => 0,
      'application' => 'ca_Ox2Rdo90ojLoUICobfcdmeRJeF2Icbzs',
      'balance_transaction' => 'txn_1ShseNAns9lY52GQK2q57ilo',
      'charge' => 'py_1ShseKPEpkzElSu4ZALo6Sfm',
      'created' => 1766584968,
      'currency' => 'eur',
      'fee_source' => 
      array (
        'charge' => 'py_1ShseKPEpkzElSu4ZALo6Sfm',
        'type' => 'charge',
      ),
      'livemode' => true,
      'originating_transaction' => 'ch_3ShsONAns9lY52GQ0yrhiZbO',
      'refunded' => false,
      'refunds' => 
      array (
        'object' => 'list',
        'data' => 
        array (
        ),
        'has_more' => false,
        'total_count' => 0,
        'url' => '/v1/application_fees/fee_1ShseKPEpkzElSu44aGupegw/refunds',
      ),
    ),
    'created_at' => '2026-01-07 19:13:04',
    'updated_at' => '2026-01-07 20:47:44',
  ),
  46 => 
  array (
    'id' => 47,
    'stripe_fee_id' => 'fee_1ShbYNPCrwFqsIfaUT7wllxl',
    'amount' => '3.75',
    'currency' => 'EUR',
    'created_at_stripe' => '2026-01-07 21:47:44',
    'stripe_account_id' => 'acct_1OhbRBPCrwFqsIfa',
    'partner_email' => 'maeva2000@inwind.it',
    'partner_name' => 'SPEEDY PIZZA DI MASSIMILIANO SILVESTRI',
    'client_id' => 358,
    'charge_id' => 'py_1ShbYNPCrwFqsIfakQzAybH6',
    'description' => 'maeva2000@inwind.it - acct_1OhbRBPCrwFqsIfa',
    'period_month' => '2025-12',
    'raw_data' => 
    array (
      'id' => 'fee_1ShbYNPCrwFqsIfaUT7wllxl',
      'object' => 'application_fee',
      'account' => 'acct_1OhbRBPCrwFqsIfa',
      'amount' => 375,
      'amount_refunded' => 0,
      'application' => 'ca_Ox2Rdo90ojLoUICobfcdmeRJeF2Icbzs',
      'balance_transaction' => 'txn_1ShbYQAns9lY52GQUWbCo8GV',
      'charge' => 'py_1ShbYNPCrwFqsIfakQzAybH6',
      'created' => 1766519251,
      'currency' => 'eur',
      'fee_source' => 
      array (
        'charge' => 'py_1ShbYNPCrwFqsIfakQzAybH6',
        'type' => 'charge',
      ),
      'livemode' => true,
      'originating_transaction' => 'ch_3ShbXbAns9lY52GQ1aboBgR2',
      'refunded' => false,
      'refunds' => 
      array (
        'object' => 'list',
        'data' => 
        array (
        ),
        'has_more' => false,
        'total_count' => 0,
        'url' => '/v1/application_fees/fee_1ShbYNPCrwFqsIfaUT7wllxl/refunds',
      ),
    ),
    'created_at' => '2026-01-07 19:13:05',
    'updated_at' => '2026-01-07 20:47:44',
  ),
  47 => 
  array (
    'id' => 48,
    'stripe_fee_id' => 'fee_1ShRLXPCrwFqsIfarnt7C8Au',
    'amount' => '2.77',
    'currency' => 'EUR',
    'created_at_stripe' => '2026-01-07 21:47:44',
    'stripe_account_id' => 'acct_1OhbRBPCrwFqsIfa',
    'partner_email' => 'maeva2000@inwind.it',
    'partner_name' => 'SPEEDY PIZZA DI MASSIMILIANO SILVESTRI',
    'client_id' => 358,
    'charge_id' => 'py_1ShRLXPCrwFqsIfa4WkU2mZv',
    'description' => 'maeva2000@inwind.it - acct_1OhbRBPCrwFqsIfa',
    'period_month' => '2025-12',
    'raw_data' => 
    array (
      'id' => 'fee_1ShRLXPCrwFqsIfarnt7C8Au',
      'object' => 'application_fee',
      'account' => 'acct_1OhbRBPCrwFqsIfa',
      'amount' => 277,
      'amount_refunded' => 0,
      'application' => 'ca_Ox2Rdo90ojLoUICobfcdmeRJeF2Icbzs',
      'balance_transaction' => 'txn_1ShRLZAns9lY52GQYDU7UJQJ',
      'charge' => 'py_1ShRLXPCrwFqsIfa4WkU2mZv',
      'created' => 1766480015,
      'currency' => 'eur',
      'fee_source' => 
      array (
        'charge' => 'py_1ShRLXPCrwFqsIfa4WkU2mZv',
        'type' => 'charge',
      ),
      'livemode' => true,
      'originating_transaction' => 'ch_3ShRKhAns9lY52GQ0lBAyswx',
      'refunded' => false,
      'refunds' => 
      array (
        'object' => 'list',
        'data' => 
        array (
        ),
        'has_more' => false,
        'total_count' => 0,
        'url' => '/v1/application_fees/fee_1ShRLXPCrwFqsIfarnt7C8Au/refunds',
      ),
    ),
    'created_at' => '2026-01-07 19:13:06',
    'updated_at' => '2026-01-07 20:47:44',
  ),
  48 => 
  array (
    'id' => 49,
    'stripe_fee_id' => 'fee_1ShECEPB7qjhlfVaZPNDsQcY',
    'amount' => '2.73',
    'currency' => 'EUR',
    'created_at_stripe' => '2026-01-07 21:47:44',
    'stripe_account_id' => 'acct_1OrlPGPB7qjhlfVa',
    'partner_email' => 'laboratoriodellapizza@gmail.com',
    'partner_name' => 'Macrì Domenico',
    'client_id' => 325,
    'charge_id' => 'py_1ShECEPB7qjhlfVaXKs2eTrr',
    'description' => 'laboratoriodellapizza@gmail.com - acct_1OrlPGPB7qjhlfVa',
    'period_month' => '2025-12',
    'raw_data' => 
    array (
      'id' => 'fee_1ShECEPB7qjhlfVaZPNDsQcY',
      'object' => 'application_fee',
      'account' => 'acct_1OrlPGPB7qjhlfVa',
      'amount' => 273,
      'amount_refunded' => 0,
      'application' => 'ca_Ox2Rdo90ojLoUICobfcdmeRJeF2Icbzs',
      'balance_transaction' => 'txn_1ShECGAns9lY52GQnl6zj153',
      'charge' => 'py_1ShECEPB7qjhlfVaXKs2eTrr',
      'created' => 1766429466,
      'currency' => 'eur',
      'fee_source' => 
      array (
        'charge' => 'py_1ShECEPB7qjhlfVaXKs2eTrr',
        'type' => 'charge',
      ),
      'livemode' => true,
      'originating_transaction' => 'ch_3ShE0uAns9lY52GQ0ydaGlUs',
      'refunded' => false,
      'refunds' => 
      array (
        'object' => 'list',
        'data' => 
        array (
        ),
        'has_more' => false,
        'total_count' => 0,
        'url' => '/v1/application_fees/fee_1ShECEPB7qjhlfVaZPNDsQcY/refunds',
      ),
    ),
    'created_at' => '2026-01-07 19:13:06',
    'updated_at' => '2026-01-07 20:47:44',
  ),
  49 => 
  array (
    'id' => 50,
    'stripe_fee_id' => 'fee_1ShDx1PMZ7tGUYNweIak0MLK',
    'amount' => '4.86',
    'currency' => 'EUR',
    'created_at_stripe' => '2026-01-07 21:47:44',
    'stripe_account_id' => 'acct_1OjmUePMZ7tGUYNw',
    'partner_email' => 'feusrl.2019@gmail.com',
    'partner_name' => 'Feu Srl',
    'client_id' => 301,
    'charge_id' => 'py_1ShDx1PMZ7tGUYNwZ5lOJx7K',
    'description' => 'feusrl.2019@gmail.com - acct_1OjmUePMZ7tGUYNw',
    'period_month' => '2025-12',
    'raw_data' => 
    array (
      'id' => 'fee_1ShDx1PMZ7tGUYNweIak0MLK',
      'object' => 'application_fee',
      'account' => 'acct_1OjmUePMZ7tGUYNw',
      'amount' => 486,
      'amount_refunded' => 0,
      'application' => 'ca_Ox2Rdo90ojLoUICobfcdmeRJeF2Icbzs',
      'balance_transaction' => 'txn_1ShDx4Ans9lY52GQtqpIu4so',
      'charge' => 'py_1ShDx1PMZ7tGUYNwZ5lOJx7K',
      'created' => 1766428523,
      'currency' => 'eur',
      'fee_source' => 
      array (
        'charge' => 'py_1ShDx1PMZ7tGUYNwZ5lOJx7K',
        'type' => 'charge',
      ),
      'livemode' => true,
      'originating_transaction' => 'ch_3ShDjoAns9lY52GQ0rRbTUjI',
      'refunded' => false,
      'refunds' => 
      array (
        'object' => 'list',
        'data' => 
        array (
        ),
        'has_more' => false,
        'total_count' => 0,
        'url' => '/v1/application_fees/fee_1ShDx1PMZ7tGUYNweIak0MLK/refunds',
      ),
    ),
    'created_at' => '2026-01-07 19:13:07',
    'updated_at' => '2026-01-07 20:47:44',
  ),
  50 => 
  array (
    'id' => 51,
    'stripe_fee_id' => 'fee_1ShDMtPIzlXORG3aOI7fIcAl',
    'amount' => '6.26',
    'currency' => 'EUR',
    'created_at_stripe' => '2026-01-07 21:47:44',
    'stripe_account_id' => 'acct_1OYpemPIzlXORG3a',
    'partner_email' => 'feusrl.2019@gmail.com',
    'partner_name' => 'Feu Srl',
    'client_id' => 301,
    'charge_id' => 'py_1ShDMtPIzlXORG3aZ2qJQwWC',
    'description' => 'feusrl.2019@gmail.com - acct_1OYpemPIzlXORG3a',
    'period_month' => '2025-12',
    'raw_data' => 
    array (
      'id' => 'fee_1ShDMtPIzlXORG3aOI7fIcAl',
      'object' => 'application_fee',
      'account' => 'acct_1OYpemPIzlXORG3a',
      'amount' => 626,
      'amount_refunded' => 0,
      'application' => 'ca_Ox2Rdo90ojLoUICobfcdmeRJeF2Icbzs',
      'balance_transaction' => 'txn_1ShDMvAns9lY52GQCfxlw9yO',
      'charge' => 'py_1ShDMtPIzlXORG3aZ2qJQwWC',
      'created' => 1766426283,
      'currency' => 'eur',
      'fee_source' => 
      array (
        'charge' => 'py_1ShDMtPIzlXORG3aZ2qJQwWC',
        'type' => 'charge',
      ),
      'livemode' => true,
      'originating_transaction' => 'ch_3ShCycAns9lY52GQ0y4NtgAt',
      'refunded' => false,
      'refunds' => 
      array (
        'object' => 'list',
        'data' => 
        array (
        ),
        'has_more' => false,
        'total_count' => 0,
        'url' => '/v1/application_fees/fee_1ShDMtPIzlXORG3aOI7fIcAl/refunds',
      ),
    ),
    'created_at' => '2026-01-07 19:13:07',
    'updated_at' => '2026-01-07 20:47:44',
  ),
  51 => 
  array (
    'id' => 52,
    'stripe_fee_id' => 'fee_1ShAWKPCrwFqsIfadmwnMpBU',
    'amount' => '2.82',
    'currency' => 'EUR',
    'created_at_stripe' => '2026-01-07 21:47:44',
    'stripe_account_id' => 'acct_1OhbRBPCrwFqsIfa',
    'partner_email' => 'maeva2000@inwind.it',
    'partner_name' => 'SPEEDY PIZZA DI MASSIMILIANO SILVESTRI',
    'client_id' => 358,
    'charge_id' => 'py_1ShAWKPCrwFqsIfaVKl9IVxv',
    'description' => 'maeva2000@inwind.it - acct_1OhbRBPCrwFqsIfa',
    'period_month' => '2025-12',
    'raw_data' => 
    array (
      'id' => 'fee_1ShAWKPCrwFqsIfadmwnMpBU',
      'object' => 'application_fee',
      'account' => 'acct_1OhbRBPCrwFqsIfa',
      'amount' => 282,
      'amount_refunded' => 0,
      'application' => 'ca_Ox2Rdo90ojLoUICobfcdmeRJeF2Icbzs',
      'balance_transaction' => 'txn_1ShAWNAns9lY52GQ4yf3Tk5f',
      'charge' => 'py_1ShAWKPCrwFqsIfaVKl9IVxv',
      'created' => 1766415336,
      'currency' => 'eur',
      'fee_source' => 
      array (
        'charge' => 'py_1ShAWKPCrwFqsIfaVKl9IVxv',
        'type' => 'charge',
      ),
      'livemode' => true,
      'originating_transaction' => 'ch_3ShAS7Ans9lY52GQ0b9ulydz',
      'refunded' => false,
      'refunds' => 
      array (
        'object' => 'list',
        'data' => 
        array (
        ),
        'has_more' => false,
        'total_count' => 0,
        'url' => '/v1/application_fees/fee_1ShAWKPCrwFqsIfadmwnMpBU/refunds',
      ),
    ),
    'created_at' => '2026-01-07 19:13:08',
    'updated_at' => '2026-01-07 20:47:44',
  ),
  52 => 
  array (
    'id' => 53,
    'stripe_fee_id' => 'fee_1SgteyPBokpe0rfChB1LCGbm',
    'amount' => '4.06',
    'currency' => 'EUR',
    'created_at_stripe' => '2026-01-07 21:47:44',
    'stripe_account_id' => 'acct_1RbPNaPBokpe0rfC',
    'partner_email' => 'aledilie87@gmail.com',
    'partner_name' => 'Pizzeria Ideale di Di Lieto Alessio',
    'client_id' => 341,
    'charge_id' => 'py_1SgteyPBokpe0rfCaXPks6hr',
    'description' => 'aledilie87@gmail.com - acct_1RbPNaPBokpe0rfC',
    'period_month' => '2025-12',
    'raw_data' => 
    array (
      'id' => 'fee_1SgteyPBokpe0rfChB1LCGbm',
      'object' => 'application_fee',
      'account' => 'acct_1RbPNaPBokpe0rfC',
      'amount' => 406,
      'amount_refunded' => 0,
      'application' => 'ca_Ox2Rdo90ojLoUICobfcdmeRJeF2Icbzs',
      'balance_transaction' => 'txn_1Sgtf0Ans9lY52GQHBUdgUsy',
      'charge' => 'py_1SgteyPBokpe0rfCaXPks6hr',
      'created' => 1766350524,
      'currency' => 'eur',
      'fee_source' => 
      array (
        'charge' => 'py_1SgteyPBokpe0rfCaXPks6hr',
        'type' => 'charge',
      ),
      'livemode' => true,
      'originating_transaction' => 'ch_3SgteLAns9lY52GQ0Cv2e8Qn',
      'refunded' => false,
      'refunds' => 
      array (
        'object' => 'list',
        'data' => 
        array (
        ),
        'has_more' => false,
        'total_count' => 0,
        'url' => '/v1/application_fees/fee_1SgteyPBokpe0rfChB1LCGbm/refunds',
      ),
    ),
    'created_at' => '2026-01-07 19:13:09',
    'updated_at' => '2026-01-07 20:47:44',
  ),
  53 => 
  array (
    'id' => 54,
    'stripe_fee_id' => 'fee_1Sgr7aPDtObfqosDkCkdUCOu',
    'amount' => '4.48',
    'currency' => 'EUR',
    'created_at_stripe' => '2026-01-07 21:47:44',
    'stripe_account_id' => 'acct_1OeD5cPDtObfqosD',
    'partner_email' => 'stripe@sushinoexperience.com',
    'partner_name' => 'Sushino SRLS',
    'client_id' => 384,
    'charge_id' => 'py_1Sgr7ZPDtObfqosDC0M9wWbP',
    'description' => 'stripe@sushinoexperience.com - acct_1OeD5cPDtObfqosD',
    'period_month' => '2025-12',
    'raw_data' => 
    array (
      'id' => 'fee_1Sgr7aPDtObfqosDkCkdUCOu',
      'object' => 'application_fee',
      'account' => 'acct_1OeD5cPDtObfqosD',
      'amount' => 448,
      'amount_refunded' => 0,
      'application' => 'ca_Ox2Rdo90ojLoUICobfcdmeRJeF2Icbzs',
      'balance_transaction' => 'txn_1Sgr7dAns9lY52GQqCTyhyuM',
      'charge' => 'py_1Sgr7ZPDtObfqosDC0M9wWbP',
      'created' => 1766340766,
      'currency' => 'eur',
      'fee_source' => 
      array (
        'charge' => 'py_1Sgr7ZPDtObfqosDC0M9wWbP',
        'type' => 'charge',
      ),
      'livemode' => true,
      'originating_transaction' => 'ch_3SgqjBAns9lY52GQ1dNg4B8e',
      'refunded' => false,
      'refunds' => 
      array (
        'object' => 'list',
        'data' => 
        array (
        ),
        'has_more' => false,
        'total_count' => 0,
        'url' => '/v1/application_fees/fee_1Sgr7aPDtObfqosDkCkdUCOu/refunds',
      ),
    ),
    'created_at' => '2026-01-07 19:13:09',
    'updated_at' => '2026-01-07 20:47:44',
  ),
  54 => 
  array (
    'id' => 55,
    'stripe_fee_id' => 'fee_1Sgr1TPNB3k6tHL8tg0svhQW',
    'amount' => '3.92',
    'currency' => 'EUR',
    'created_at_stripe' => '2026-01-07 21:47:44',
    'stripe_account_id' => 'acct_1RYoXnPNB3k6tHL8',
    'partner_email' => 'pizzeriaitrecanti@gmail.com',
    'partner_name' => 'Pizzeria I 3 Canti di Bibbiani Mirco & C. SAS',
    'client_id' => 340,
    'charge_id' => 'py_1Sgr1TPNB3k6tHL8fIzl2qUJ',
    'description' => 'pizzeriaitrecanti@gmail.com - acct_1RYoXnPNB3k6tHL8',
    'period_month' => '2025-12',
    'raw_data' => 
    array (
      'id' => 'fee_1Sgr1TPNB3k6tHL8tg0svhQW',
      'object' => 'application_fee',
      'account' => 'acct_1RYoXnPNB3k6tHL8',
      'amount' => 392,
      'amount_refunded' => 0,
      'application' => 'ca_Ox2Rdo90ojLoUICobfcdmeRJeF2Icbzs',
      'balance_transaction' => 'txn_1Sgr1VAns9lY52GQcbGs9SiJ',
      'charge' => 'py_1Sgr1TPNB3k6tHL8fIzl2qUJ',
      'created' => 1766340387,
      'currency' => 'eur',
      'fee_source' => 
      array (
        'charge' => 'py_1Sgr1TPNB3k6tHL8fIzl2qUJ',
        'type' => 'charge',
      ),
      'livemode' => true,
      'originating_transaction' => 'ch_3SgpB0Ans9lY52GQ1wu4e1AZ',
      'refunded' => false,
      'refunds' => 
      array (
        'object' => 'list',
        'data' => 
        array (
        ),
        'has_more' => false,
        'total_count' => 0,
        'url' => '/v1/application_fees/fee_1Sgr1TPNB3k6tHL8tg0svhQW/refunds',
      ),
    ),
    'created_at' => '2026-01-07 19:13:10',
    'updated_at' => '2026-01-07 20:47:44',
  ),
  55 => 
  array (
    'id' => 56,
    'stripe_fee_id' => 'fee_1SgqhNPFSTNU0nUGQozlAOKr',
    'amount' => '3.03',
    'currency' => 'EUR',
    'created_at_stripe' => '2026-01-07 21:47:44',
    'stripe_account_id' => 'acct_1OtvsmPFSTNU0nUG',
    'partner_email' => 'fortipizza@gmail.com',
    'partner_name' => 'Forti Pizza e Torta',
    'client_id' => 303,
    'charge_id' => 'py_1SgqhNPFSTNU0nUGHeM6bnjg',
    'description' => 'fortipizza@gmail.com - acct_1OtvsmPFSTNU0nUG',
    'period_month' => '2025-12',
    'raw_data' => 
    array (
      'id' => 'fee_1SgqhNPFSTNU0nUGQozlAOKr',
      'object' => 'application_fee',
      'account' => 'acct_1OtvsmPFSTNU0nUG',
      'amount' => 303,
      'amount_refunded' => 0,
      'application' => 'ca_Ox2Rdo90ojLoUICobfcdmeRJeF2Icbzs',
      'balance_transaction' => 'txn_1SgqhPAns9lY52GQmwuUXjGL',
      'charge' => 'py_1SgqhNPFSTNU0nUGHeM6bnjg',
      'created' => 1766339141,
      'currency' => 'eur',
      'fee_source' => 
      array (
        'charge' => 'py_1SgqhNPFSTNU0nUGHeM6bnjg',
        'type' => 'charge',
      ),
      'livemode' => true,
      'originating_transaction' => 'ch_3Sgqg2Ans9lY52GQ1PaTLFiF',
      'refunded' => false,
      'refunds' => 
      array (
        'object' => 'list',
        'data' => 
        array (
        ),
        'has_more' => false,
        'total_count' => 0,
        'url' => '/v1/application_fees/fee_1SgqhNPFSTNU0nUGQozlAOKr/refunds',
      ),
    ),
    'created_at' => '2026-01-07 19:13:10',
    'updated_at' => '2026-01-07 20:47:44',
  ),
  56 => 
  array (
    'id' => 57,
    'stripe_fee_id' => 'fee_1SgqX8PIzlXORG3areZKbtOk',
    'amount' => '4.95',
    'currency' => 'EUR',
    'created_at_stripe' => '2026-01-07 21:47:44',
    'stripe_account_id' => 'acct_1OYpemPIzlXORG3a',
    'partner_email' => 'feusrl.2019@gmail.com',
    'partner_name' => 'Feu Srl',
    'client_id' => 301,
    'charge_id' => 'py_1SgqX8PIzlXORG3aKDGYYWTn',
    'description' => 'feusrl.2019@gmail.com - acct_1OYpemPIzlXORG3a',
    'period_month' => '2025-12',
    'raw_data' => 
    array (
      'id' => 'fee_1SgqX8PIzlXORG3areZKbtOk',
      'object' => 'application_fee',
      'account' => 'acct_1OYpemPIzlXORG3a',
      'amount' => 495,
      'amount_refunded' => 0,
      'application' => 'ca_Ox2Rdo90ojLoUICobfcdmeRJeF2Icbzs',
      'balance_transaction' => 'txn_1SgqXAAns9lY52GQmRU4UwQh',
      'charge' => 'py_1SgqX8PIzlXORG3aKDGYYWTn',
      'created' => 1766338506,
      'currency' => 'eur',
      'fee_source' => 
      array (
        'charge' => 'py_1SgqX8PIzlXORG3aKDGYYWTn',
        'type' => 'charge',
      ),
      'livemode' => true,
      'originating_transaction' => 'ch_3SgpvCAns9lY52GQ0LZDW9ge',
      'refunded' => false,
      'refunds' => 
      array (
        'object' => 'list',
        'data' => 
        array (
        ),
        'has_more' => false,
        'total_count' => 0,
        'url' => '/v1/application_fees/fee_1SgqX8PIzlXORG3areZKbtOk/refunds',
      ),
    ),
    'created_at' => '2026-01-07 19:13:11',
    'updated_at' => '2026-01-07 20:47:44',
  ),
  57 => 
  array (
    'id' => 58,
    'stripe_fee_id' => 'fee_1Sgl5tPIzlXORG3ajrLNZc09',
    'amount' => '4.74',
    'currency' => 'EUR',
    'created_at_stripe' => '2026-01-07 21:47:44',
    'stripe_account_id' => 'acct_1OYpemPIzlXORG3a',
    'partner_email' => 'feusrl.2019@gmail.com',
    'partner_name' => 'Feu Srl',
    'client_id' => 301,
    'charge_id' => 'py_1Sgl5sPIzlXORG3alsVIznKV',
    'description' => 'feusrl.2019@gmail.com - acct_1OYpemPIzlXORG3a',
    'period_month' => '2025-12',
    'raw_data' => 
    array (
      'id' => 'fee_1Sgl5tPIzlXORG3ajrLNZc09',
      'object' => 'application_fee',
      'account' => 'acct_1OYpemPIzlXORG3a',
      'amount' => 474,
      'amount_refunded' => 0,
      'application' => 'ca_Ox2Rdo90ojLoUICobfcdmeRJeF2Icbzs',
      'balance_transaction' => 'txn_1Sgl5vAns9lY52GQ4QvaMIj2',
      'charge' => 'py_1Sgl5sPIzlXORG3alsVIznKV',
      'created' => 1766317597,
      'currency' => 'eur',
      'fee_source' => 
      array (
        'charge' => 'py_1Sgl5sPIzlXORG3alsVIznKV',
        'type' => 'charge',
      ),
      'livemode' => true,
      'originating_transaction' => 'ch_3Sgkx0Ans9lY52GQ0kh3LwUV',
      'refunded' => false,
      'refunds' => 
      array (
        'object' => 'list',
        'data' => 
        array (
        ),
        'has_more' => false,
        'total_count' => 0,
        'url' => '/v1/application_fees/fee_1Sgl5tPIzlXORG3ajrLNZc09/refunds',
      ),
    ),
    'created_at' => '2026-01-07 19:13:12',
    'updated_at' => '2026-01-07 20:47:44',
  ),
  58 => 
  array (
    'id' => 59,
    'stripe_fee_id' => 'fee_1SgWmdPIzlXORG3aJZYIzE9y',
    'amount' => '5.94',
    'currency' => 'EUR',
    'created_at_stripe' => '2026-01-07 21:47:44',
    'stripe_account_id' => 'acct_1OYpemPIzlXORG3a',
    'partner_email' => 'feusrl.2019@gmail.com',
    'partner_name' => 'Feu Srl',
    'client_id' => 301,
    'charge_id' => 'py_1SgWmdPIzlXORG3af1e54DmZ',
    'description' => 'feusrl.2019@gmail.com - acct_1OYpemPIzlXORG3a',
    'period_month' => '2025-12',
    'raw_data' => 
    array (
      'id' => 'fee_1SgWmdPIzlXORG3aJZYIzE9y',
      'object' => 'application_fee',
      'account' => 'acct_1OYpemPIzlXORG3a',
      'amount' => 594,
      'amount_refunded' => 0,
      'application' => 'ca_Ox2Rdo90ojLoUICobfcdmeRJeF2Icbzs',
      'balance_transaction' => 'txn_1SgWmgAns9lY52GQSUSJkhZu',
      'charge' => 'py_1SgWmdPIzlXORG3af1e54DmZ',
      'created' => 1766262587,
      'currency' => 'eur',
      'fee_source' => 
      array (
        'charge' => 'py_1SgWmdPIzlXORG3af1e54DmZ',
        'type' => 'charge',
      ),
      'livemode' => true,
      'originating_transaction' => 'ch_3SgW6rAns9lY52GQ0QHAbrlB',
      'refunded' => false,
      'refunds' => 
      array (
        'object' => 'list',
        'data' => 
        array (
        ),
        'has_more' => false,
        'total_count' => 0,
        'url' => '/v1/application_fees/fee_1SgWmdPIzlXORG3aJZYIzE9y/refunds',
      ),
    ),
    'created_at' => '2026-01-07 19:13:12',
    'updated_at' => '2026-01-07 20:47:44',
  ),
  59 => 
  array (
    'id' => 60,
    'stripe_fee_id' => 'fee_1SgVdhPDtObfqosDPlz0eMJS',
    'amount' => '4.22',
    'currency' => 'EUR',
    'created_at_stripe' => '2026-01-07 21:47:44',
    'stripe_account_id' => 'acct_1OeD5cPDtObfqosD',
    'partner_email' => 'stripe@sushinoexperience.com',
    'partner_name' => 'Sushino SRLS',
    'client_id' => 384,
    'charge_id' => 'py_1SgVdhPDtObfqosDTsaeIRlw',
    'description' => 'stripe@sushinoexperience.com - acct_1OeD5cPDtObfqosD',
    'period_month' => '2025-12',
    'raw_data' => 
    array (
      'id' => 'fee_1SgVdhPDtObfqosDPlz0eMJS',
      'object' => 'application_fee',
      'account' => 'acct_1OeD5cPDtObfqosD',
      'amount' => 422,
      'amount_refunded' => 0,
      'application' => 'ca_Ox2Rdo90ojLoUICobfcdmeRJeF2Icbzs',
      'balance_transaction' => 'txn_1SgVdkAns9lY52GQPZOEtOhO',
      'charge' => 'py_1SgVdhPDtObfqosDTsaeIRlw',
      'created' => 1766258189,
      'currency' => 'eur',
      'fee_source' => 
      array (
        'charge' => 'py_1SgVdhPDtObfqosDTsaeIRlw',
        'type' => 'charge',
      ),
      'livemode' => true,
      'originating_transaction' => 'ch_3SgVZ3Ans9lY52GQ05aG3uzM',
      'refunded' => false,
      'refunds' => 
      array (
        'object' => 'list',
        'data' => 
        array (
        ),
        'has_more' => false,
        'total_count' => 0,
        'url' => '/v1/application_fees/fee_1SgVdhPDtObfqosDPlz0eMJS/refunds',
      ),
    ),
    'created_at' => '2026-01-07 19:13:13',
    'updated_at' => '2026-01-07 20:47:44',
  ),
  60 => 
  array (
    'id' => 61,
    'stripe_fee_id' => 'fee_1SgUQuPCrwFqsIfaOlc8AfDq',
    'amount' => '2.97',
    'currency' => 'EUR',
    'created_at_stripe' => '2026-01-07 21:47:44',
    'stripe_account_id' => 'acct_1OhbRBPCrwFqsIfa',
    'partner_email' => 'maeva2000@inwind.it',
    'partner_name' => 'SPEEDY PIZZA DI MASSIMILIANO SILVESTRI',
    'client_id' => 358,
    'charge_id' => 'py_1SgUQuPCrwFqsIfatA1JHQ3j',
    'description' => 'maeva2000@inwind.it - acct_1OhbRBPCrwFqsIfa',
    'period_month' => '2025-12',
    'raw_data' => 
    array (
      'id' => 'fee_1SgUQuPCrwFqsIfaOlc8AfDq',
      'object' => 'application_fee',
      'account' => 'acct_1OhbRBPCrwFqsIfa',
      'amount' => 297,
      'amount_refunded' => 0,
      'application' => 'ca_Ox2Rdo90ojLoUICobfcdmeRJeF2Icbzs',
      'balance_transaction' => 'txn_1SgUQwAns9lY52GQybL6vuUt',
      'charge' => 'py_1SgUQuPCrwFqsIfatA1JHQ3j',
      'created' => 1766253552,
      'currency' => 'eur',
      'fee_source' => 
      array (
        'charge' => 'py_1SgUQuPCrwFqsIfatA1JHQ3j',
        'type' => 'charge',
      ),
      'livemode' => true,
      'originating_transaction' => 'ch_3SgTrnAns9lY52GQ0aXkRmFN',
      'refunded' => false,
      'refunds' => 
      array (
        'object' => 'list',
        'data' => 
        array (
        ),
        'has_more' => false,
        'total_count' => 0,
        'url' => '/v1/application_fees/fee_1SgUQuPCrwFqsIfaOlc8AfDq/refunds',
      ),
    ),
    'created_at' => '2026-01-07 19:13:13',
    'updated_at' => '2026-01-07 20:47:44',
  ),
  61 => 
  array (
    'id' => 62,
    'stripe_fee_id' => 'fee_1SgUEyPB7qjhlfVa0a7jQ3Lv',
    'amount' => '3.21',
    'currency' => 'EUR',
    'created_at_stripe' => '2026-01-07 21:47:44',
    'stripe_account_id' => 'acct_1OrlPGPB7qjhlfVa',
    'partner_email' => 'laboratoriodellapizza@gmail.com',
    'partner_name' => 'Macrì Domenico',
    'client_id' => 325,
    'charge_id' => 'py_1SgUEyPB7qjhlfVaCueAI5WO',
    'description' => 'laboratoriodellapizza@gmail.com - acct_1OrlPGPB7qjhlfVa',
    'period_month' => '2025-12',
    'raw_data' => 
    array (
      'id' => 'fee_1SgUEyPB7qjhlfVa0a7jQ3Lv',
      'object' => 'application_fee',
      'account' => 'acct_1OrlPGPB7qjhlfVa',
      'amount' => 321,
      'amount_refunded' => 0,
      'application' => 'ca_Ox2Rdo90ojLoUICobfcdmeRJeF2Icbzs',
      'balance_transaction' => 'txn_1SgUF0Ans9lY52GQThYsLZV1',
      'charge' => 'py_1SgUEyPB7qjhlfVaCueAI5WO',
      'created' => 1766252812,
      'currency' => 'eur',
      'fee_source' => 
      array (
        'charge' => 'py_1SgUEyPB7qjhlfVaCueAI5WO',
        'type' => 'charge',
      ),
      'livemode' => true,
      'originating_transaction' => 'ch_3SgUEQAns9lY52GQ1vrspTKr',
      'refunded' => false,
      'refunds' => 
      array (
        'object' => 'list',
        'data' => 
        array (
        ),
        'has_more' => false,
        'total_count' => 0,
        'url' => '/v1/application_fees/fee_1SgUEyPB7qjhlfVa0a7jQ3Lv/refunds',
      ),
    ),
    'created_at' => '2026-01-07 19:13:14',
    'updated_at' => '2026-01-07 20:47:44',
  ),
  62 => 
  array (
    'id' => 63,
    'stripe_fee_id' => 'fee_1SgT7VPCrwFqsIfaSUUUpALm',
    'amount' => '2.88',
    'currency' => 'EUR',
    'created_at_stripe' => '2026-01-07 21:47:44',
    'stripe_account_id' => 'acct_1OhbRBPCrwFqsIfa',
    'partner_email' => 'maeva2000@inwind.it',
    'partner_name' => 'SPEEDY PIZZA DI MASSIMILIANO SILVESTRI',
    'client_id' => 358,
    'charge_id' => 'py_1SgT7UPCrwFqsIfaPmefWF28',
    'description' => 'maeva2000@inwind.it - acct_1OhbRBPCrwFqsIfa',
    'period_month' => '2025-12',
    'raw_data' => 
    array (
      'id' => 'fee_1SgT7VPCrwFqsIfaSUUUpALm',
      'object' => 'application_fee',
      'account' => 'acct_1OhbRBPCrwFqsIfa',
      'amount' => 288,
      'amount_refunded' => 0,
      'application' => 'ca_Ox2Rdo90ojLoUICobfcdmeRJeF2Icbzs',
      'balance_transaction' => 'txn_1SgT7XAns9lY52GQzrqOzsiC',
      'charge' => 'py_1SgT7UPCrwFqsIfaPmefWF28',
      'created' => 1766248505,
      'currency' => 'eur',
      'fee_source' => 
      array (
        'charge' => 'py_1SgT7UPCrwFqsIfaPmefWF28',
        'type' => 'charge',
      ),
      'livemode' => true,
      'originating_transaction' => 'ch_3SgT4yAns9lY52GQ11rTz9BB',
      'refunded' => false,
      'refunds' => 
      array (
        'object' => 'list',
        'data' => 
        array (
        ),
        'has_more' => false,
        'total_count' => 0,
        'url' => '/v1/application_fees/fee_1SgT7VPCrwFqsIfaSUUUpALm/refunds',
      ),
    ),
    'created_at' => '2026-01-07 19:13:15',
    'updated_at' => '2026-01-07 20:47:44',
  ),
  63 => 
  array (
    'id' => 64,
    'stripe_fee_id' => 'fee_1SgSFdPB7qjhlfVa90WgwfRM',
    'amount' => '2.93',
    'currency' => 'EUR',
    'created_at_stripe' => '2026-01-07 21:47:44',
    'stripe_account_id' => 'acct_1OrlPGPB7qjhlfVa',
    'partner_email' => 'laboratoriodellapizza@gmail.com',
    'partner_name' => 'Macrì Domenico',
    'client_id' => 325,
    'charge_id' => 'py_1SgSFdPB7qjhlfVaitUzBxvJ',
    'description' => 'laboratoriodellapizza@gmail.com - acct_1OrlPGPB7qjhlfVa',
    'period_month' => '2025-12',
    'raw_data' => 
    array (
      'id' => 'fee_1SgSFdPB7qjhlfVa90WgwfRM',
      'object' => 'application_fee',
      'account' => 'acct_1OrlPGPB7qjhlfVa',
      'amount' => 293,
      'amount_refunded' => 0,
      'application' => 'ca_Ox2Rdo90ojLoUICobfcdmeRJeF2Icbzs',
      'balance_transaction' => 'txn_1SgSFgAns9lY52GQuKsJxMA8',
      'charge' => 'py_1SgSFdPB7qjhlfVaitUzBxvJ',
      'created' => 1766245165,
      'currency' => 'eur',
      'fee_source' => 
      array (
        'charge' => 'py_1SgSFdPB7qjhlfVaitUzBxvJ',
        'type' => 'charge',
      ),
      'livemode' => true,
      'originating_transaction' => 'ch_3SgPr9Ans9lY52GQ0ApOkXAs',
      'refunded' => false,
      'refunds' => 
      array (
        'object' => 'list',
        'data' => 
        array (
        ),
        'has_more' => false,
        'total_count' => 0,
        'url' => '/v1/application_fees/fee_1SgSFdPB7qjhlfVa90WgwfRM/refunds',
      ),
    ),
    'created_at' => '2026-01-07 19:13:15',
    'updated_at' => '2026-01-07 20:47:44',
  ),
  64 => 
  array (
    'id' => 65,
    'stripe_fee_id' => 'fee_1SgRdxPB8fwsTso3NE4p8br3',
    'amount' => '2.74',
    'currency' => 'EUR',
    'created_at_stripe' => '2026-01-07 21:47:44',
    'stripe_account_id' => 'acct_1Oe1NCPB8fwsTso3',
    'partner_email' => 'alepizza2000@gmail.com',
    'partner_name' => 'PIZZA E TORTA DA ALEPIZZA DI BIANCHI ALESSIO',
    'client_id' => 336,
    'charge_id' => 'py_1SgRdxPB8fwsTso3WTptpklk',
    'description' => 'alepizza2000@gmail.com - acct_1Oe1NCPB8fwsTso3',
    'period_month' => '2025-12',
    'raw_data' => 
    array (
      'id' => 'fee_1SgRdxPB8fwsTso3NE4p8br3',
      'object' => 'application_fee',
      'account' => 'acct_1Oe1NCPB8fwsTso3',
      'amount' => 274,
      'amount_refunded' => 0,
      'application' => 'ca_Ox2Rdo90ojLoUICobfcdmeRJeF2Icbzs',
      'balance_transaction' => 'txn_1SgRdzAns9lY52GQ6nhj6gZ8',
      'charge' => 'py_1SgRdxPB8fwsTso3WTptpklk',
      'created' => 1766242829,
      'currency' => 'eur',
      'fee_source' => 
      array (
        'charge' => 'py_1SgRdxPB8fwsTso3WTptpklk',
        'type' => 'charge',
      ),
      'livemode' => true,
      'originating_transaction' => 'ch_3SgN6EAns9lY52GQ1gGxSQci',
      'refunded' => false,
      'refunds' => 
      array (
        'object' => 'list',
        'data' => 
        array (
        ),
        'has_more' => false,
        'total_count' => 0,
        'url' => '/v1/application_fees/fee_1SgRdxPB8fwsTso3NE4p8br3/refunds',
      ),
    ),
    'created_at' => '2026-01-07 19:13:16',
    'updated_at' => '2026-01-07 20:47:44',
  ),
  65 => 
  array (
    'id' => 66,
    'stripe_fee_id' => 'fee_1SgQhKPEpkzElSu4KT6VHohT',
    'amount' => '3.48',
    'currency' => 'EUR',
    'created_at_stripe' => '2026-01-07 21:47:44',
    'stripe_account_id' => 'acct_1OrHzGPEpkzElSu4',
    'partner_email' => 'andreadellomodarme@gmail.com',
    'partner_name' => 'PUNTO P DI ANDREA DELL\'OMODARME',
    'client_id' => 383,
    'charge_id' => 'py_1SgQhKPEpkzElSu4vO1uJ4wl',
    'description' => 'andreadellomodarme@gmail.com - acct_1OrHzGPEpkzElSu4',
    'period_month' => '2025-12',
    'raw_data' => 
    array (
      'id' => 'fee_1SgQhKPEpkzElSu4KT6VHohT',
      'object' => 'application_fee',
      'account' => 'acct_1OrHzGPEpkzElSu4',
      'amount' => 348,
      'amount_refunded' => 0,
      'application' => 'ca_Ox2Rdo90ojLoUICobfcdmeRJeF2Icbzs',
      'balance_transaction' => 'txn_1SgQhNAns9lY52GQJgjfDTOc',
      'charge' => 'py_1SgQhKPEpkzElSu4vO1uJ4wl',
      'created' => 1766239194,
      'currency' => 'eur',
      'fee_source' => 
      array (
        'charge' => 'py_1SgQhKPEpkzElSu4vO1uJ4wl',
        'type' => 'charge',
      ),
      'livemode' => true,
      'originating_transaction' => 'ch_3SgPVjAns9lY52GQ0i0bBxNr',
      'refunded' => false,
      'refunds' => 
      array (
        'object' => 'list',
        'data' => 
        array (
        ),
        'has_more' => false,
        'total_count' => 0,
        'url' => '/v1/application_fees/fee_1SgQhKPEpkzElSu4KT6VHohT/refunds',
      ),
    ),
    'created_at' => '2026-01-07 19:13:17',
    'updated_at' => '2026-01-07 20:47:44',
  ),
  66 => 
  array (
    'id' => 67,
    'stripe_fee_id' => 'fee_1SgQdSPEpkzElSu4U9fhsJE5',
    'amount' => '3.69',
    'currency' => 'EUR',
    'created_at_stripe' => '2026-01-07 21:47:44',
    'stripe_account_id' => 'acct_1OrHzGPEpkzElSu4',
    'partner_email' => 'andreadellomodarme@gmail.com',
    'partner_name' => 'PUNTO P DI ANDREA DELL\'OMODARME',
    'client_id' => 383,
    'charge_id' => 'py_1SgQdSPEpkzElSu41N1DTTIs',
    'description' => 'andreadellomodarme@gmail.com - acct_1OrHzGPEpkzElSu4',
    'period_month' => '2025-12',
    'raw_data' => 
    array (
      'id' => 'fee_1SgQdSPEpkzElSu4U9fhsJE5',
      'object' => 'application_fee',
      'account' => 'acct_1OrHzGPEpkzElSu4',
      'amount' => 369,
      'amount_refunded' => 0,
      'application' => 'ca_Ox2Rdo90ojLoUICobfcdmeRJeF2Icbzs',
      'balance_transaction' => 'txn_1SgQdUAns9lY52GQyLXBwO4p',
      'charge' => 'py_1SgQdSPEpkzElSu41N1DTTIs',
      'created' => 1766238954,
      'currency' => 'eur',
      'fee_source' => 
      array (
        'charge' => 'py_1SgQdSPEpkzElSu41N1DTTIs',
        'type' => 'charge',
      ),
      'livemode' => true,
      'originating_transaction' => 'ch_3SgNe0Ans9lY52GQ1hk5LCgH',
      'refunded' => false,
      'refunds' => 
      array (
        'object' => 'list',
        'data' => 
        array (
        ),
        'has_more' => false,
        'total_count' => 0,
        'url' => '/v1/application_fees/fee_1SgQdSPEpkzElSu4U9fhsJE5/refunds',
      ),
    ),
    'created_at' => '2026-01-07 19:13:17',
    'updated_at' => '2026-01-07 20:47:44',
  ),
  67 => 
  array (
    'id' => 68,
    'stripe_fee_id' => 'fee_1SgPynPCrwFqsIfaYSNnjJTX',
    'amount' => '3.51',
    'currency' => 'EUR',
    'created_at_stripe' => '2026-01-07 21:47:44',
    'stripe_account_id' => 'acct_1OhbRBPCrwFqsIfa',
    'partner_email' => 'maeva2000@inwind.it',
    'partner_name' => 'SPEEDY PIZZA DI MASSIMILIANO SILVESTRI',
    'client_id' => 358,
    'charge_id' => 'py_1SgPynPCrwFqsIfatoQTp1Iz',
    'description' => 'maeva2000@inwind.it - acct_1OhbRBPCrwFqsIfa',
    'period_month' => '2025-12',
    'raw_data' => 
    array (
      'id' => 'fee_1SgPynPCrwFqsIfaYSNnjJTX',
      'object' => 'application_fee',
      'account' => 'acct_1OhbRBPCrwFqsIfa',
      'amount' => 351,
      'amount_refunded' => 0,
      'application' => 'ca_Ox2Rdo90ojLoUICobfcdmeRJeF2Icbzs',
      'balance_transaction' => 'txn_1SgPyqAns9lY52GQxoewU4Az',
      'charge' => 'py_1SgPynPCrwFqsIfatoQTp1Iz',
      'created' => 1766236433,
      'currency' => 'eur',
      'fee_source' => 
      array (
        'charge' => 'py_1SgPynPCrwFqsIfatoQTp1Iz',
        'type' => 'charge',
      ),
      'livemode' => true,
      'originating_transaction' => 'ch_3SgPeSAns9lY52GQ0aDEqpYi',
      'refunded' => false,
      'refunds' => 
      array (
        'object' => 'list',
        'data' => 
        array (
        ),
        'has_more' => false,
        'total_count' => 0,
        'url' => '/v1/application_fees/fee_1SgPynPCrwFqsIfaYSNnjJTX/refunds',
      ),
    ),
    'created_at' => '2026-01-07 19:13:18',
    'updated_at' => '2026-01-07 20:47:44',
  ),
  68 => 
  array (
    'id' => 69,
    'stripe_fee_id' => 'fee_1SgPJbPCrwFqsIfaxK96soPt',
    'amount' => '2.83',
    'currency' => 'EUR',
    'created_at_stripe' => '2026-01-07 21:47:44',
    'stripe_account_id' => 'acct_1OhbRBPCrwFqsIfa',
    'partner_email' => 'maeva2000@inwind.it',
    'partner_name' => 'SPEEDY PIZZA DI MASSIMILIANO SILVESTRI',
    'client_id' => 358,
    'charge_id' => 'py_1SgPJbPCrwFqsIfay27V8jjc',
    'description' => 'maeva2000@inwind.it - acct_1OhbRBPCrwFqsIfa',
    'period_month' => '2025-12',
    'raw_data' => 
    array (
      'id' => 'fee_1SgPJbPCrwFqsIfaxK96soPt',
      'object' => 'application_fee',
      'account' => 'acct_1OhbRBPCrwFqsIfa',
      'amount' => 283,
      'amount_refunded' => 0,
      'application' => 'ca_Ox2Rdo90ojLoUICobfcdmeRJeF2Icbzs',
      'balance_transaction' => 'txn_1SgPJdAns9lY52GQDSkmox58',
      'charge' => 'py_1SgPJbPCrwFqsIfay27V8jjc',
      'created' => 1766233879,
      'currency' => 'eur',
      'fee_source' => 
      array (
        'charge' => 'py_1SgPJbPCrwFqsIfay27V8jjc',
        'type' => 'charge',
      ),
      'livemode' => true,
      'originating_transaction' => 'ch_3SgL2zAns9lY52GQ1kUGElZh',
      'refunded' => false,
      'refunds' => 
      array (
        'object' => 'list',
        'data' => 
        array (
        ),
        'has_more' => false,
        'total_count' => 0,
        'url' => '/v1/application_fees/fee_1SgPJbPCrwFqsIfaxK96soPt/refunds',
      ),
    ),
    'created_at' => '2026-01-07 19:13:18',
    'updated_at' => '2026-01-07 20:47:44',
  ),
  69 => 
  array (
    'id' => 70,
    'stripe_fee_id' => 'fee_1Sg7yfPIzlXORG3aJ22SOjbY',
    'amount' => '4.93',
    'currency' => 'EUR',
    'created_at_stripe' => '2026-01-07 21:47:44',
    'stripe_account_id' => 'acct_1OYpemPIzlXORG3a',
    'partner_email' => 'feusrl.2019@gmail.com',
    'partner_name' => 'Feu Srl',
    'client_id' => 301,
    'charge_id' => 'py_1Sg7yfPIzlXORG3aU0LEX1Jo',
    'description' => 'feusrl.2019@gmail.com - acct_1OYpemPIzlXORG3a',
    'period_month' => '2025-12',
    'raw_data' => 
    array (
      'id' => 'fee_1Sg7yfPIzlXORG3aJ22SOjbY',
      'object' => 'application_fee',
      'account' => 'acct_1OYpemPIzlXORG3a',
      'amount' => 493,
      'amount_refunded' => 0,
      'application' => 'ca_Ox2Rdo90ojLoUICobfcdmeRJeF2Icbzs',
      'balance_transaction' => 'txn_1Sg7yiAns9lY52GQwsZPW5El',
      'charge' => 'py_1Sg7yfPIzlXORG3aU0LEX1Jo',
      'created' => 1766167233,
      'currency' => 'eur',
      'fee_source' => 
      array (
        'charge' => 'py_1Sg7yfPIzlXORG3aU0LEX1Jo',
        'type' => 'charge',
      ),
      'livemode' => true,
      'originating_transaction' => 'ch_3Sg7sAAns9lY52GQ1EFnl9BE',
      'refunded' => false,
      'refunds' => 
      array (
        'object' => 'list',
        'data' => 
        array (
        ),
        'has_more' => false,
        'total_count' => 0,
        'url' => '/v1/application_fees/fee_1Sg7yfPIzlXORG3aJ22SOjbY/refunds',
      ),
    ),
    'created_at' => '2026-01-07 19:13:19',
    'updated_at' => '2026-01-07 20:47:44',
  ),
  70 => 
  array (
    'id' => 71,
    'stripe_fee_id' => 'fee_1Sg7yXPIzlXORG3as4FFsEtl',
    'amount' => '4.70',
    'currency' => 'EUR',
    'created_at_stripe' => '2026-01-07 21:47:44',
    'stripe_account_id' => 'acct_1OYpemPIzlXORG3a',
    'partner_email' => 'feusrl.2019@gmail.com',
    'partner_name' => 'Feu Srl',
    'client_id' => 301,
    'charge_id' => 'py_1Sg7yXPIzlXORG3amVuRj2D7',
    'description' => 'feusrl.2019@gmail.com - acct_1OYpemPIzlXORG3a',
    'period_month' => '2025-12',
    'raw_data' => 
    array (
      'id' => 'fee_1Sg7yXPIzlXORG3as4FFsEtl',
      'object' => 'application_fee',
      'account' => 'acct_1OYpemPIzlXORG3a',
      'amount' => 470,
      'amount_refunded' => 0,
      'application' => 'ca_Ox2Rdo90ojLoUICobfcdmeRJeF2Icbzs',
      'balance_transaction' => 'txn_1Sg7yZAns9lY52GQxKilUBNc',
      'charge' => 'py_1Sg7yXPIzlXORG3amVuRj2D7',
      'created' => 1766167225,
      'currency' => 'eur',
      'fee_source' => 
      array (
        'charge' => 'py_1Sg7yXPIzlXORG3amVuRj2D7',
        'type' => 'charge',
      ),
      'livemode' => true,
      'originating_transaction' => 'ch_3Sg7niAns9lY52GQ0sKsRe7R',
      'refunded' => false,
      'refunds' => 
      array (
        'object' => 'list',
        'data' => 
        array (
        ),
        'has_more' => false,
        'total_count' => 0,
        'url' => '/v1/application_fees/fee_1Sg7yXPIzlXORG3as4FFsEtl/refunds',
      ),
    ),
    'created_at' => '2026-01-07 19:13:20',
    'updated_at' => '2026-01-07 20:47:44',
  ),
  71 => 
  array (
    'id' => 72,
    'stripe_fee_id' => 'fee_1Sg7qIPNlXDSKQBIOhI14eCY',
    'amount' => '3.79',
    'currency' => 'EUR',
    'created_at_stripe' => '2026-01-07 21:47:44',
    'stripe_account_id' => 'acct_1QgqazPNlXDSKQBI',
    'partner_email' => 'amministrazione@incarne.it',
    'partner_name' => 'INCARNE SRL',
    'client_id' => 317,
    'charge_id' => 'py_1Sg7qHPNlXDSKQBIDIYlPLUZ',
    'description' => 'amministrazione@incarne.it - acct_1QgqazPNlXDSKQBI',
    'period_month' => '2025-12',
    'raw_data' => 
    array (
      'id' => 'fee_1Sg7qIPNlXDSKQBIOhI14eCY',
      'object' => 'application_fee',
      'account' => 'acct_1QgqazPNlXDSKQBI',
      'amount' => 379,
      'amount_refunded' => 0,
      'application' => 'ca_Ox2Rdo90ojLoUICobfcdmeRJeF2Icbzs',
      'balance_transaction' => 'txn_1Sg7qKAns9lY52GQ2icRovKx',
      'charge' => 'py_1Sg7qHPNlXDSKQBIDIYlPLUZ',
      'created' => 1766166714,
      'currency' => 'eur',
      'fee_source' => 
      array (
        'charge' => 'py_1Sg7qHPNlXDSKQBIDIYlPLUZ',
        'type' => 'charge',
      ),
      'livemode' => true,
      'originating_transaction' => 'ch_3Sg7ftAns9lY52GQ0gLYeApP',
      'refunded' => false,
      'refunds' => 
      array (
        'object' => 'list',
        'data' => 
        array (
        ),
        'has_more' => false,
        'total_count' => 0,
        'url' => '/v1/application_fees/fee_1Sg7qIPNlXDSKQBIOhI14eCY/refunds',
      ),
    ),
    'created_at' => '2026-01-07 19:13:20',
    'updated_at' => '2026-01-07 20:47:44',
  ),
  72 => 
  array (
    'id' => 73,
    'stripe_fee_id' => 'fee_1Sg7VmArGwCSIIveS7zGRfIc',
    'amount' => '2.96',
    'currency' => 'EUR',
    'created_at_stripe' => '2026-01-07 21:47:44',
    'stripe_account_id' => 'acct_1SRyIkArGwCSIIve',
    'partner_email' => 'ordinazioni@sbriciolopizza.it',
    'partner_name' => 'PACIFIC JAFFE S.R.L.',
    'client_id' => 333,
    'charge_id' => 'py_1Sg7VmArGwCSIIvePBaUuk08',
    'description' => 'ordinazioni@sbriciolopizza.it - acct_1SRyIkArGwCSIIve',
    'period_month' => '2025-12',
    'raw_data' => 
    array (
      'id' => 'fee_1Sg7VmArGwCSIIveS7zGRfIc',
      'object' => 'application_fee',
      'account' => 'acct_1SRyIkArGwCSIIve',
      'amount' => 296,
      'amount_refunded' => 0,
      'application' => 'ca_Ox2Rdo90ojLoUICobfcdmeRJeF2Icbzs',
      'balance_transaction' => 'txn_1Sg7VpAns9lY52GQg27b4tnt',
      'charge' => 'py_1Sg7VmArGwCSIIvePBaUuk08',
      'created' => 1766165442,
      'currency' => 'eur',
      'fee_source' => 
      array (
        'charge' => 'py_1Sg7VmArGwCSIIvePBaUuk08',
        'type' => 'charge',
      ),
      'livemode' => true,
      'originating_transaction' => 'ch_3Sg7PzAns9lY52GQ1gXafdsV',
      'refunded' => false,
      'refunds' => 
      array (
        'object' => 'list',
        'data' => 
        array (
        ),
        'has_more' => false,
        'total_count' => 0,
        'url' => '/v1/application_fees/fee_1Sg7VmArGwCSIIveS7zGRfIc/refunds',
      ),
    ),
    'created_at' => '2026-01-07 19:13:21',
    'updated_at' => '2026-01-07 20:47:44',
  ),
  73 => 
  array (
    'id' => 74,
    'stripe_fee_id' => 'fee_1Sg6YYPEpkzElSu4vzXONKA1',
    'amount' => '2.94',
    'currency' => 'EUR',
    'created_at_stripe' => '2026-01-07 21:47:44',
    'stripe_account_id' => 'acct_1OrHzGPEpkzElSu4',
    'partner_email' => 'andreadellomodarme@gmail.com',
    'partner_name' => 'PUNTO P DI ANDREA DELL\'OMODARME',
    'client_id' => 383,
    'charge_id' => 'py_1Sg6YYPEpkzElSu43jbzYtV9',
    'description' => 'andreadellomodarme@gmail.com - acct_1OrHzGPEpkzElSu4',
    'period_month' => '2025-12',
    'raw_data' => 
    array (
      'id' => 'fee_1Sg6YYPEpkzElSu4vzXONKA1',
      'object' => 'application_fee',
      'account' => 'acct_1OrHzGPEpkzElSu4',
      'amount' => 294,
      'amount_refunded' => 0,
      'application' => 'ca_Ox2Rdo90ojLoUICobfcdmeRJeF2Icbzs',
      'balance_transaction' => 'txn_1Sg6YbAns9lY52GQIblcCneT',
      'charge' => 'py_1Sg6YYPEpkzElSu43jbzYtV9',
      'created' => 1766161770,
      'currency' => 'eur',
      'fee_source' => 
      array (
        'charge' => 'py_1Sg6YYPEpkzElSu43jbzYtV9',
        'type' => 'charge',
      ),
      'livemode' => true,
      'originating_transaction' => 'ch_3Sg6NGAns9lY52GQ0fJi79IK',
      'refunded' => false,
      'refunds' => 
      array (
        'object' => 'list',
        'data' => 
        array (
        ),
        'has_more' => false,
        'total_count' => 0,
        'url' => '/v1/application_fees/fee_1Sg6YYPEpkzElSu4vzXONKA1/refunds',
      ),
    ),
    'created_at' => '2026-01-07 19:13:22',
    'updated_at' => '2026-01-07 20:47:44',
  ),
  74 => 
  array (
    'id' => 75,
    'stripe_fee_id' => 'fee_1Sfwn8PCrwFqsIfa9M3LARPD',
    'amount' => '2.89',
    'currency' => 'EUR',
    'created_at_stripe' => '2026-01-07 21:47:44',
    'stripe_account_id' => 'acct_1OhbRBPCrwFqsIfa',
    'partner_email' => 'maeva2000@inwind.it',
    'partner_name' => 'SPEEDY PIZZA DI MASSIMILIANO SILVESTRI',
    'client_id' => 358,
    'charge_id' => 'py_1Sfwn8PCrwFqsIfa2PfTKTJ9',
    'description' => 'maeva2000@inwind.it - acct_1OhbRBPCrwFqsIfa',
    'period_month' => '2025-12',
    'raw_data' => 
    array (
      'id' => 'fee_1Sfwn8PCrwFqsIfa9M3LARPD',
      'object' => 'application_fee',
      'account' => 'acct_1OhbRBPCrwFqsIfa',
      'amount' => 289,
      'amount_refunded' => 0,
      'application' => 'ca_Ox2Rdo90ojLoUICobfcdmeRJeF2Icbzs',
      'balance_transaction' => 'txn_1SfwnAAns9lY52GQUzI2pjPG',
      'charge' => 'py_1Sfwn8PCrwFqsIfa2PfTKTJ9',
      'created' => 1766124234,
      'currency' => 'eur',
      'fee_source' => 
      array (
        'charge' => 'py_1Sfwn8PCrwFqsIfa2PfTKTJ9',
        'type' => 'charge',
      ),
      'livemode' => true,
      'originating_transaction' => 'ch_3SfwkZAns9lY52GQ1i1aCrRH',
      'refunded' => false,
      'refunds' => 
      array (
        'object' => 'list',
        'data' => 
        array (
        ),
        'has_more' => false,
        'total_count' => 0,
        'url' => '/v1/application_fees/fee_1Sfwn8PCrwFqsIfa9M3LARPD/refunds',
      ),
    ),
    'created_at' => '2026-01-07 19:13:22',
    'updated_at' => '2026-01-07 20:47:44',
  ),
  75 => 
  array (
    'id' => 76,
    'stripe_fee_id' => 'fee_1Sfmu9ACqrpp0TG5qpvki084',
    'amount' => '3.86',
    'currency' => 'EUR',
    'created_at_stripe' => '2026-01-07 21:47:44',
    'stripe_account_id' => 'acct_1RyUiVACqrpp0TG5',
    'partner_email' => 'pizzeriaitrecanti@gmail.com',
    'partner_name' => 'Pizzeria I 3 Canti di Bibbiani Mirco & C. SAS',
    'client_id' => 340,
    'charge_id' => 'py_1Sfmu8ACqrpp0TG5ywVL52U2',
    'description' => 'pizzeriaitrecanti@gmail.com - acct_1RyUiVACqrpp0TG5',
    'period_month' => '2025-12',
    'raw_data' => 
    array (
      'id' => 'fee_1Sfmu9ACqrpp0TG5qpvki084',
      'object' => 'application_fee',
      'account' => 'acct_1RyUiVACqrpp0TG5',
      'amount' => 386,
      'amount_refunded' => 0,
      'application' => 'ca_Ox2Rdo90ojLoUICobfcdmeRJeF2Icbzs',
      'balance_transaction' => 'txn_1SfmuBAns9lY52GQ9mlfwUU1',
      'charge' => 'py_1Sfmu8ACqrpp0TG5ywVL52U2',
      'created' => 1766086229,
      'currency' => 'eur',
      'fee_source' => 
      array (
        'charge' => 'py_1Sfmu8ACqrpp0TG5ywVL52U2',
        'type' => 'charge',
      ),
      'livemode' => true,
      'originating_transaction' => 'ch_3Sfmt9Ans9lY52GQ119gmGdJ',
      'refunded' => false,
      'refunds' => 
      array (
        'object' => 'list',
        'data' => 
        array (
        ),
        'has_more' => false,
        'total_count' => 0,
        'url' => '/v1/application_fees/fee_1Sfmu9ACqrpp0TG5qpvki084/refunds',
      ),
    ),
    'created_at' => '2026-01-07 19:13:23',
    'updated_at' => '2026-01-07 20:47:44',
  ),
  76 => 
  array (
    'id' => 77,
    'stripe_fee_id' => 'fee_1Sfk91PCrwFqsIfa0FjpBjvc',
    'amount' => '2.87',
    'currency' => 'EUR',
    'created_at_stripe' => '2026-01-07 21:47:44',
    'stripe_account_id' => 'acct_1OhbRBPCrwFqsIfa',
    'partner_email' => 'maeva2000@inwind.it',
    'partner_name' => 'SPEEDY PIZZA DI MASSIMILIANO SILVESTRI',
    'client_id' => 358,
    'charge_id' => 'py_1Sfk91PCrwFqsIfa92b1shEC',
    'description' => 'maeva2000@inwind.it - acct_1OhbRBPCrwFqsIfa',
    'period_month' => '2025-12',
    'raw_data' => 
    array (
      'id' => 'fee_1Sfk91PCrwFqsIfa0FjpBjvc',
      'object' => 'application_fee',
      'account' => 'acct_1OhbRBPCrwFqsIfa',
      'amount' => 287,
      'amount_refunded' => 0,
      'application' => 'ca_Ox2Rdo90ojLoUICobfcdmeRJeF2Icbzs',
      'balance_transaction' => 'txn_1Sfk93Ans9lY52GQOkQb2hhJ',
      'charge' => 'py_1Sfk91PCrwFqsIfa92b1shEC',
      'created' => 1766075619,
      'currency' => 'eur',
      'fee_source' => 
      array (
        'charge' => 'py_1Sfk91PCrwFqsIfa92b1shEC',
        'type' => 'charge',
      ),
      'livemode' => true,
      'originating_transaction' => 'ch_3Sfk8HAns9lY52GQ1nfwOTOH',
      'refunded' => false,
      'refunds' => 
      array (
        'object' => 'list',
        'data' => 
        array (
        ),
        'has_more' => false,
        'total_count' => 0,
        'url' => '/v1/application_fees/fee_1Sfk91PCrwFqsIfa0FjpBjvc/refunds',
      ),
    ),
    'created_at' => '2026-01-07 19:13:23',
    'updated_at' => '2026-01-07 20:47:44',
  ),
  77 => 
  array (
    'id' => 78,
    'stripe_fee_id' => 'fee_1SfPdsPB7qjhlfVafsXNZRZc',
    'amount' => '2.96',
    'currency' => 'EUR',
    'created_at_stripe' => '2026-01-07 21:47:44',
    'stripe_account_id' => 'acct_1OrlPGPB7qjhlfVa',
    'partner_email' => 'laboratoriodellapizza@gmail.com',
    'partner_name' => 'Macrì Domenico',
    'client_id' => 325,
    'charge_id' => 'py_1SfPdsPB7qjhlfVaBCS3g1r9',
    'description' => 'laboratoriodellapizza@gmail.com - acct_1OrlPGPB7qjhlfVa',
    'period_month' => '2025-12',
    'raw_data' => 
    array (
      'id' => 'fee_1SfPdsPB7qjhlfVafsXNZRZc',
      'object' => 'application_fee',
      'account' => 'acct_1OrlPGPB7qjhlfVa',
      'amount' => 296,
      'amount_refunded' => 0,
      'application' => 'ca_Ox2Rdo90ojLoUICobfcdmeRJeF2Icbzs',
      'balance_transaction' => 'txn_1SfPduAns9lY52GQB12BYoyf',
      'charge' => 'py_1SfPdsPB7qjhlfVaBCS3g1r9',
      'created' => 1765996808,
      'currency' => 'eur',
      'fee_source' => 
      array (
        'charge' => 'py_1SfPdsPB7qjhlfVaBCS3g1r9',
        'type' => 'charge',
      ),
      'livemode' => true,
      'originating_transaction' => 'ch_3SfPdKAns9lY52GQ1vrapFBp',
      'refunded' => false,
      'refunds' => 
      array (
        'object' => 'list',
        'data' => 
        array (
        ),
        'has_more' => false,
        'total_count' => 0,
        'url' => '/v1/application_fees/fee_1SfPdsPB7qjhlfVafsXNZRZc/refunds',
      ),
    ),
    'created_at' => '2026-01-07 19:13:24',
    'updated_at' => '2026-01-07 20:47:44',
  ),
  78 => 
  array (
    'id' => 79,
    'stripe_fee_id' => 'fee_1SfOdDPEpkzElSu4ZoC8qar2',
    'amount' => '3.37',
    'currency' => 'EUR',
    'created_at_stripe' => '2026-01-07 21:47:44',
    'stripe_account_id' => 'acct_1OrHzGPEpkzElSu4',
    'partner_email' => 'andreadellomodarme@gmail.com',
    'partner_name' => 'PUNTO P DI ANDREA DELL\'OMODARME',
    'client_id' => 383,
    'charge_id' => 'py_1SfOdDPEpkzElSu4Cd9xbCW4',
    'description' => 'andreadellomodarme@gmail.com - acct_1OrHzGPEpkzElSu4',
    'period_month' => '2025-12',
    'raw_data' => 
    array (
      'id' => 'fee_1SfOdDPEpkzElSu4ZoC8qar2',
      'object' => 'application_fee',
      'account' => 'acct_1OrHzGPEpkzElSu4',
      'amount' => 337,
      'amount_refunded' => 0,
      'application' => 'ca_Ox2Rdo90ojLoUICobfcdmeRJeF2Icbzs',
      'balance_transaction' => 'txn_1SfOdFAns9lY52GQxdEfOMXA',
      'charge' => 'py_1SfOdDPEpkzElSu4Cd9xbCW4',
      'created' => 1765992923,
      'currency' => 'eur',
      'fee_source' => 
      array (
        'charge' => 'py_1SfOdDPEpkzElSu4Cd9xbCW4',
        'type' => 'charge',
      ),
      'livemode' => true,
      'originating_transaction' => 'ch_3SfOb7Ans9lY52GQ01FiHyho',
      'refunded' => false,
      'refunds' => 
      array (
        'object' => 'list',
        'data' => 
        array (
        ),
        'has_more' => false,
        'total_count' => 0,
        'url' => '/v1/application_fees/fee_1SfOdDPEpkzElSu4ZoC8qar2/refunds',
      ),
    ),
    'created_at' => '2026-01-07 19:13:24',
    'updated_at' => '2026-01-07 20:47:44',
  ),
  79 => 
  array (
    'id' => 80,
    'stripe_fee_id' => 'fee_1Sf2XRPIzlXORG3aV4pK4Dfk',
    'amount' => '6.13',
    'currency' => 'EUR',
    'created_at_stripe' => '2026-01-07 21:47:44',
    'stripe_account_id' => 'acct_1OYpemPIzlXORG3a',
    'partner_email' => 'feusrl.2019@gmail.com',
    'partner_name' => 'Feu Srl',
    'client_id' => 301,
    'charge_id' => 'py_1Sf2XRPIzlXORG3aJWDcZngF',
    'description' => 'feusrl.2019@gmail.com - acct_1OYpemPIzlXORG3a',
    'period_month' => '2025-12',
    'raw_data' => 
    array (
      'id' => 'fee_1Sf2XRPIzlXORG3aV4pK4Dfk',
      'object' => 'application_fee',
      'account' => 'acct_1OYpemPIzlXORG3a',
      'amount' => 613,
      'amount_refunded' => 0,
      'application' => 'ca_Ox2Rdo90ojLoUICobfcdmeRJeF2Icbzs',
      'balance_transaction' => 'txn_1Sf2XUAns9lY52GQu6oqEl5e',
      'charge' => 'py_1Sf2XRPIzlXORG3aJWDcZngF',
      'created' => 1765907997,
      'currency' => 'eur',
      'fee_source' => 
      array (
        'charge' => 'py_1Sf2XRPIzlXORG3aJWDcZngF',
        'type' => 'charge',
      ),
      'livemode' => true,
      'originating_transaction' => 'ch_3Sf2V1Ans9lY52GQ1kXKJ5f2',
      'refunded' => false,
      'refunds' => 
      array (
        'object' => 'list',
        'data' => 
        array (
        ),
        'has_more' => false,
        'total_count' => 0,
        'url' => '/v1/application_fees/fee_1Sf2XRPIzlXORG3aV4pK4Dfk/refunds',
      ),
    ),
    'created_at' => '2026-01-07 19:13:25',
    'updated_at' => '2026-01-07 20:47:44',
  ),
  80 => 
  array (
    'id' => 81,
    'stripe_fee_id' => 'fee_1SewJVAcaExTZKe8Qgq1n0oL',
    'amount' => '3.75',
    'currency' => 'EUR',
    'created_at_stripe' => '2026-01-07 21:47:44',
    'stripe_account_id' => 'acct_1QsMHWAcaExTZKe8',
    'partner_email' => 'nclcucc@gmail.com',
    'partner_name' => 'La Loggia sul Mare SRL',
    'client_id' => 322,
    'charge_id' => 'py_1SewJVAcaExTZKe8zoRITxjX',
    'description' => 'nclcucc@gmail.com - acct_1QsMHWAcaExTZKe8',
    'period_month' => '2025-12',
    'raw_data' => 
    array (
      'id' => 'fee_1SewJVAcaExTZKe8Qgq1n0oL',
      'object' => 'application_fee',
      'account' => 'acct_1QsMHWAcaExTZKe8',
      'amount' => 375,
      'amount_refunded' => 0,
      'application' => 'ca_Ox2Rdo90ojLoUICobfcdmeRJeF2Icbzs',
      'balance_transaction' => 'txn_1SewJXAns9lY52GQq2tr2BrA',
      'charge' => 'py_1SewJVAcaExTZKe8zoRITxjX',
      'created' => 1765884069,
      'currency' => 'eur',
      'fee_source' => 
      array (
        'charge' => 'py_1SewJVAcaExTZKe8zoRITxjX',
        'type' => 'charge',
      ),
      'livemode' => true,
      'originating_transaction' => 'ch_3Sevm3Ans9lY52GQ1z0Kz1Tn',
      'refunded' => false,
      'refunds' => 
      array (
        'object' => 'list',
        'data' => 
        array (
        ),
        'has_more' => false,
        'total_count' => 0,
        'url' => '/v1/application_fees/fee_1SewJVAcaExTZKe8Qgq1n0oL/refunds',
      ),
    ),
    'created_at' => '2026-01-07 19:13:26',
    'updated_at' => '2026-01-07 20:47:44',
  ),
  81 => 
  array (
    'id' => 82,
    'stripe_fee_id' => 'fee_1SefmePB7qjhlfVavcKf7Fy4',
    'amount' => '2.98',
    'currency' => 'EUR',
    'created_at_stripe' => '2026-01-07 21:47:44',
    'stripe_account_id' => 'acct_1OrlPGPB7qjhlfVa',
    'partner_email' => 'laboratoriodellapizza@gmail.com',
    'partner_name' => 'Macrì Domenico',
    'client_id' => 325,
    'charge_id' => 'py_1SefmePB7qjhlfVa8Vly3qSN',
    'description' => 'laboratoriodellapizza@gmail.com - acct_1OrlPGPB7qjhlfVa',
    'period_month' => '2025-12',
    'raw_data' => 
    array (
      'id' => 'fee_1SefmePB7qjhlfVavcKf7Fy4',
      'object' => 'application_fee',
      'account' => 'acct_1OrlPGPB7qjhlfVa',
      'amount' => 298,
      'amount_refunded' => 0,
      'application' => 'ca_Ox2Rdo90ojLoUICobfcdmeRJeF2Icbzs',
      'balance_transaction' => 'txn_1SefmgAns9lY52GQCInIFcys',
      'charge' => 'py_1SefmePB7qjhlfVa8Vly3qSN',
      'created' => 1765820528,
      'currency' => 'eur',
      'fee_source' => 
      array (
        'charge' => 'py_1SefmePB7qjhlfVa8Vly3qSN',
        'type' => 'charge',
      ),
      'livemode' => true,
      'originating_transaction' => 'ch_3Sefa9Ans9lY52GQ1l4DnPXj',
      'refunded' => false,
      'refunds' => 
      array (
        'object' => 'list',
        'data' => 
        array (
        ),
        'has_more' => false,
        'total_count' => 0,
        'url' => '/v1/application_fees/fee_1SefmePB7qjhlfVavcKf7Fy4/refunds',
      ),
    ),
    'created_at' => '2026-01-07 19:13:26',
    'updated_at' => '2026-01-07 20:47:44',
  ),
  82 => 
  array (
    'id' => 83,
    'stripe_fee_id' => 'fee_1SefbdPB8fwsTso3pWMZ4MLp',
    'amount' => '2.81',
    'currency' => 'EUR',
    'created_at_stripe' => '2026-01-07 21:47:44',
    'stripe_account_id' => 'acct_1Oe1NCPB8fwsTso3',
    'partner_email' => 'alepizza2000@gmail.com',
    'partner_name' => 'PIZZA E TORTA DA ALEPIZZA DI BIANCHI ALESSIO',
    'client_id' => 336,
    'charge_id' => 'py_1SefbdPB8fwsTso3B74voeSh',
    'description' => 'alepizza2000@gmail.com - acct_1Oe1NCPB8fwsTso3',
    'period_month' => '2025-12',
    'raw_data' => 
    array (
      'id' => 'fee_1SefbdPB8fwsTso3pWMZ4MLp',
      'object' => 'application_fee',
      'account' => 'acct_1Oe1NCPB8fwsTso3',
      'amount' => 281,
      'amount_refunded' => 0,
      'application' => 'ca_Ox2Rdo90ojLoUICobfcdmeRJeF2Icbzs',
      'balance_transaction' => 'txn_1SefbfAns9lY52GQ5Wa0wf7s',
      'charge' => 'py_1SefbdPB8fwsTso3B74voeSh',
      'created' => 1765819845,
      'currency' => 'eur',
      'fee_source' => 
      array (
        'charge' => 'py_1SefbdPB8fwsTso3B74voeSh',
        'type' => 'charge',
      ),
      'livemode' => true,
      'originating_transaction' => 'ch_3SefZSAns9lY52GQ0YMKOpT7',
      'refunded' => false,
      'refunds' => 
      array (
        'object' => 'list',
        'data' => 
        array (
        ),
        'has_more' => false,
        'total_count' => 0,
        'url' => '/v1/application_fees/fee_1SefbdPB8fwsTso3pWMZ4MLp/refunds',
      ),
    ),
    'created_at' => '2026-01-07 19:13:27',
    'updated_at' => '2026-01-07 20:47:44',
  ),
  83 => 
  array (
    'id' => 84,
    'stripe_fee_id' => 'fee_1SeLZXPEpkzElSu4ihpSOGzf',
    'amount' => '3.61',
    'currency' => 'EUR',
    'created_at_stripe' => '2026-01-07 21:47:44',
    'stripe_account_id' => 'acct_1OrHzGPEpkzElSu4',
    'partner_email' => 'andreadellomodarme@gmail.com',
    'partner_name' => 'PUNTO P DI ANDREA DELL\'OMODARME',
    'client_id' => 383,
    'charge_id' => 'py_1SeLZWPEpkzElSu4GBoPWKzu',
    'description' => 'andreadellomodarme@gmail.com - acct_1OrHzGPEpkzElSu4',
    'period_month' => '2025-12',
    'raw_data' => 
    array (
      'id' => 'fee_1SeLZXPEpkzElSu4ihpSOGzf',
      'object' => 'application_fee',
      'account' => 'acct_1OrHzGPEpkzElSu4',
      'amount' => 361,
      'amount_refunded' => 0,
      'application' => 'ca_Ox2Rdo90ojLoUICobfcdmeRJeF2Icbzs',
      'balance_transaction' => 'txn_1SeLZaAns9lY52GQIX0nDIqE',
      'charge' => 'py_1SeLZWPEpkzElSu4GBoPWKzu',
      'created' => 1765742835,
      'currency' => 'eur',
      'fee_source' => 
      array (
        'charge' => 'py_1SeLZWPEpkzElSu4GBoPWKzu',
        'type' => 'charge',
      ),
      'livemode' => true,
      'originating_transaction' => 'ch_3SeLX7Ans9lY52GQ1AZistiu',
      'refunded' => false,
      'refunds' => 
      array (
        'object' => 'list',
        'data' => 
        array (
        ),
        'has_more' => false,
        'total_count' => 0,
        'url' => '/v1/application_fees/fee_1SeLZXPEpkzElSu4ihpSOGzf/refunds',
      ),
    ),
    'created_at' => '2026-01-07 19:13:28',
    'updated_at' => '2026-01-07 20:47:44',
  ),
  84 => 
  array (
    'id' => 85,
    'stripe_fee_id' => 'fee_1SeKxfAcaExTZKe8IK4Xrghi',
    'amount' => '3.99',
    'currency' => 'EUR',
    'created_at_stripe' => '2026-01-07 21:47:44',
    'stripe_account_id' => 'acct_1QsMHWAcaExTZKe8',
    'partner_email' => 'nclcucc@gmail.com',
    'partner_name' => 'La Loggia sul Mare SRL',
    'client_id' => 322,
    'charge_id' => 'py_1SeKxeAcaExTZKe8lEOZqeD2',
    'description' => 'nclcucc@gmail.com - acct_1QsMHWAcaExTZKe8',
    'period_month' => '2025-12',
    'raw_data' => 
    array (
      'id' => 'fee_1SeKxfAcaExTZKe8IK4Xrghi',
      'object' => 'application_fee',
      'account' => 'acct_1QsMHWAcaExTZKe8',
      'amount' => 399,
      'amount_refunded' => 0,
      'application' => 'ca_Ox2Rdo90ojLoUICobfcdmeRJeF2Icbzs',
      'balance_transaction' => 'txn_1SeKxhAns9lY52GQY3Z6foWz',
      'charge' => 'py_1SeKxeAcaExTZKe8lEOZqeD2',
      'created' => 1765740487,
      'currency' => 'eur',
      'fee_source' => 
      array (
        'charge' => 'py_1SeKxeAcaExTZKe8lEOZqeD2',
        'type' => 'charge',
      ),
      'livemode' => true,
      'originating_transaction' => 'ch_3SeJk2Ans9lY52GQ0eHYM7Zf',
      'refunded' => false,
      'refunds' => 
      array (
        'object' => 'list',
        'data' => 
        array (
        ),
        'has_more' => false,
        'total_count' => 0,
        'url' => '/v1/application_fees/fee_1SeKxfAcaExTZKe8IK4Xrghi/refunds',
      ),
    ),
    'created_at' => '2026-01-07 19:13:28',
    'updated_at' => '2026-01-07 20:47:44',
  ),
  85 => 
  array (
    'id' => 86,
    'stripe_fee_id' => 'fee_1SeKdZPAESt8veHwgVlavI6h',
    'amount' => '2.73',
    'currency' => 'EUR',
    'created_at_stripe' => '2026-01-07 21:47:44',
    'stripe_account_id' => 'acct_1ROCdFPAESt8veHw',
    'partner_email' => 'anticatradizione1950@gmail.com',
    'partner_name' => 'Osteria Antica Tradizione srls.',
    'client_id' => 332,
    'charge_id' => 'py_1SeKdZPAESt8veHw0RAS7IKU',
    'description' => 'anticatradizione1950@gmail.com - acct_1ROCdFPAESt8veHw',
    'period_month' => '2025-12',
    'raw_data' => 
    array (
      'id' => 'fee_1SeKdZPAESt8veHwgVlavI6h',
      'object' => 'application_fee',
      'account' => 'acct_1ROCdFPAESt8veHw',
      'amount' => 273,
      'amount_refunded' => 0,
      'application' => 'ca_Ox2Rdo90ojLoUICobfcdmeRJeF2Icbzs',
      'balance_transaction' => 'txn_1SeKdcAns9lY52GQJKr2YZR7',
      'charge' => 'py_1SeKdZPAESt8veHw0RAS7IKU',
      'created' => 1765739241,
      'currency' => 'eur',
      'fee_source' => 
      array (
        'charge' => 'py_1SeKdZPAESt8veHw0RAS7IKU',
        'type' => 'charge',
      ),
      'livemode' => true,
      'originating_transaction' => 'ch_3SeJiZAns9lY52GQ1Gs10JIb',
      'refunded' => false,
      'refunds' => 
      array (
        'object' => 'list',
        'data' => 
        array (
        ),
        'has_more' => false,
        'total_count' => 0,
        'url' => '/v1/application_fees/fee_1SeKdZPAESt8veHwgVlavI6h/refunds',
      ),
    ),
    'created_at' => '2026-01-07 19:13:29',
    'updated_at' => '2026-01-07 20:47:44',
  ),
  86 => 
  array (
    'id' => 87,
    'stripe_fee_id' => 'fee_1SeJlcPIzlXORG3aiUU2t62M',
    'amount' => '5.50',
    'currency' => 'EUR',
    'created_at_stripe' => '2026-01-07 21:47:44',
    'stripe_account_id' => 'acct_1OYpemPIzlXORG3a',
    'partner_email' => 'feusrl.2019@gmail.com',
    'partner_name' => 'Feu Srl',
    'client_id' => 301,
    'charge_id' => 'py_1SeJlcPIzlXORG3aAlzUfC5w',
    'description' => 'feusrl.2019@gmail.com - acct_1OYpemPIzlXORG3a',
    'period_month' => '2025-12',
    'raw_data' => 
    array (
      'id' => 'fee_1SeJlcPIzlXORG3aiUU2t62M',
      'object' => 'application_fee',
      'account' => 'acct_1OYpemPIzlXORG3a',
      'amount' => 550,
      'amount_refunded' => 0,
      'application' => 'ca_Ox2Rdo90ojLoUICobfcdmeRJeF2Icbzs',
      'balance_transaction' => 'txn_1SeJlfAns9lY52GQLzn8zJhd',
      'charge' => 'py_1SeJlcPIzlXORG3aAlzUfC5w',
      'created' => 1765735896,
      'currency' => 'eur',
      'fee_source' => 
      array (
        'charge' => 'py_1SeJlcPIzlXORG3aAlzUfC5w',
        'type' => 'charge',
      ),
      'livemode' => true,
      'originating_transaction' => 'ch_3SeJh4Ans9lY52GQ0UcThc6Q',
      'refunded' => false,
      'refunds' => 
      array (
        'object' => 'list',
        'data' => 
        array (
        ),
        'has_more' => false,
        'total_count' => 0,
        'url' => '/v1/application_fees/fee_1SeJlcPIzlXORG3aiUU2t62M/refunds',
      ),
    ),
    'created_at' => '2026-01-07 19:13:29',
    'updated_at' => '2026-01-07 20:47:44',
  ),
  87 => 
  array (
    'id' => 88,
    'stripe_fee_id' => 'fee_1SeDUnPIzlXORG3aQiyNOmkc',
    'amount' => '4.74',
    'currency' => 'EUR',
    'created_at_stripe' => '2026-01-07 21:47:44',
    'stripe_account_id' => 'acct_1OYpemPIzlXORG3a',
    'partner_email' => 'feusrl.2019@gmail.com',
    'partner_name' => 'Feu Srl',
    'client_id' => 301,
    'charge_id' => 'py_1SeDUnPIzlXORG3amE5A2ANy',
    'description' => 'feusrl.2019@gmail.com - acct_1OYpemPIzlXORG3a',
    'period_month' => '2025-12',
    'raw_data' => 
    array (
      'id' => 'fee_1SeDUnPIzlXORG3aQiyNOmkc',
      'object' => 'application_fee',
      'account' => 'acct_1OYpemPIzlXORG3a',
      'amount' => 474,
      'amount_refunded' => 0,
      'application' => 'ca_Ox2Rdo90ojLoUICobfcdmeRJeF2Icbzs',
      'balance_transaction' => 'txn_1SeDUqAns9lY52GQoQIaoQ9a',
      'charge' => 'py_1SeDUnPIzlXORG3amE5A2ANy',
      'created' => 1765711789,
      'currency' => 'eur',
      'fee_source' => 
      array (
        'charge' => 'py_1SeDUnPIzlXORG3amE5A2ANy',
        'type' => 'charge',
      ),
      'livemode' => true,
      'originating_transaction' => 'ch_3SeDR4Ans9lY52GQ1K4m4h66',
      'refunded' => false,
      'refunds' => 
      array (
        'object' => 'list',
        'data' => 
        array (
        ),
        'has_more' => false,
        'total_count' => 0,
        'url' => '/v1/application_fees/fee_1SeDUnPIzlXORG3aQiyNOmkc/refunds',
      ),
    ),
    'created_at' => '2026-01-07 19:13:30',
    'updated_at' => '2026-01-07 20:47:44',
  ),
  88 => 
  array (
    'id' => 89,
    'stripe_fee_id' => 'fee_1SdycOPNB3k6tHL8YpOBDbbe',
    'amount' => '3.87',
    'currency' => 'EUR',
    'created_at_stripe' => '2026-01-07 21:47:44',
    'stripe_account_id' => 'acct_1RYoXnPNB3k6tHL8',
    'partner_email' => 'pizzeriaitrecanti@gmail.com',
    'partner_name' => 'Pizzeria I 3 Canti di Bibbiani Mirco & C. SAS',
    'client_id' => 340,
    'charge_id' => 'py_1SdycOPNB3k6tHL8j0A3GC4A',
    'description' => 'pizzeriaitrecanti@gmail.com - acct_1RYoXnPNB3k6tHL8',
    'period_month' => '2025-12',
    'raw_data' => 
    array (
      'id' => 'fee_1SdycOPNB3k6tHL8YpOBDbbe',
      'object' => 'application_fee',
      'account' => 'acct_1RYoXnPNB3k6tHL8',
      'amount' => 387,
      'amount_refunded' => 0,
      'application' => 'ca_Ox2Rdo90ojLoUICobfcdmeRJeF2Icbzs',
      'balance_transaction' => 'txn_1SdycQAns9lY52GQlTJ7mcUW',
      'charge' => 'py_1SdycOPNB3k6tHL8j0A3GC4A',
      'created' => 1765654600,
      'currency' => 'eur',
      'fee_source' => 
      array (
        'charge' => 'py_1SdycOPNB3k6tHL8j0A3GC4A',
        'type' => 'charge',
      ),
      'livemode' => true,
      'originating_transaction' => 'ch_3Sdxs7Ans9lY52GQ1EjcfHJA',
      'refunded' => false,
      'refunds' => 
      array (
        'object' => 'list',
        'data' => 
        array (
        ),
        'has_more' => false,
        'total_count' => 0,
        'url' => '/v1/application_fees/fee_1SdycOPNB3k6tHL8YpOBDbbe/refunds',
      ),
    ),
    'created_at' => '2026-01-07 19:13:31',
    'updated_at' => '2026-01-07 20:47:44',
  ),
  89 => 
  array (
    'id' => 90,
    'stripe_fee_id' => 'fee_1Sdxd9PIzlXORG3accl189JY',
    'amount' => '5.14',
    'currency' => 'EUR',
    'created_at_stripe' => '2026-01-07 21:47:44',
    'stripe_account_id' => 'acct_1OYpemPIzlXORG3a',
    'partner_email' => 'feusrl.2019@gmail.com',
    'partner_name' => 'Feu Srl',
    'client_id' => 301,
    'charge_id' => 'py_1Sdxd8PIzlXORG3aQ2ppA9j2',
    'description' => 'feusrl.2019@gmail.com - acct_1OYpemPIzlXORG3a',
    'period_month' => '2025-12',
    'raw_data' => 
    array (
      'id' => 'fee_1Sdxd9PIzlXORG3accl189JY',
      'object' => 'application_fee',
      'account' => 'acct_1OYpemPIzlXORG3a',
      'amount' => 514,
      'amount_refunded' => 0,
      'application' => 'ca_Ox2Rdo90ojLoUICobfcdmeRJeF2Icbzs',
      'balance_transaction' => 'txn_1SdxdBAns9lY52GQ13k8N6Rt',
      'charge' => 'py_1Sdxd8PIzlXORG3aQ2ppA9j2',
      'created' => 1765650803,
      'currency' => 'eur',
      'fee_source' => 
      array (
        'charge' => 'py_1Sdxd8PIzlXORG3aQ2ppA9j2',
        'type' => 'charge',
      ),
      'livemode' => true,
      'originating_transaction' => 'ch_3SdxKVAns9lY52GQ1eRpb44j',
      'refunded' => false,
      'refunds' => 
      array (
        'object' => 'list',
        'data' => 
        array (
        ),
        'has_more' => false,
        'total_count' => 0,
        'url' => '/v1/application_fees/fee_1Sdxd9PIzlXORG3accl189JY/refunds',
      ),
    ),
    'created_at' => '2026-01-07 19:13:31',
    'updated_at' => '2026-01-07 20:47:44',
  ),
  90 => 
  array (
    'id' => 91,
    'stripe_fee_id' => 'fee_1SdvGlArGwCSIIvewX202xP5',
    'amount' => '3.50',
    'currency' => 'EUR',
    'created_at_stripe' => '2026-01-07 21:47:44',
    'stripe_account_id' => 'acct_1SRyIkArGwCSIIve',
    'partner_email' => 'ordinazioni@sbriciolopizza.it',
    'partner_name' => 'PACIFIC JAFFE S.R.L.',
    'client_id' => 333,
    'charge_id' => 'py_1SdvGlArGwCSIIve8DeLJYkW',
    'description' => 'ordinazioni@sbriciolopizza.it - acct_1SRyIkArGwCSIIve',
    'period_month' => '2025-12',
    'raw_data' => 
    array (
      'id' => 'fee_1SdvGlArGwCSIIvewX202xP5',
      'object' => 'application_fee',
      'account' => 'acct_1SRyIkArGwCSIIve',
      'amount' => 350,
      'amount_refunded' => 0,
      'application' => 'ca_Ox2Rdo90ojLoUICobfcdmeRJeF2Icbzs',
      'balance_transaction' => 'txn_1SdvGnAns9lY52GQ33ZG0GqD',
      'charge' => 'py_1SdvGlArGwCSIIve8DeLJYkW',
      'created' => 1765641727,
      'currency' => 'eur',
      'fee_source' => 
      array (
        'charge' => 'py_1SdvGlArGwCSIIve8DeLJYkW',
        'type' => 'charge',
      ),
      'livemode' => true,
      'originating_transaction' => 'ch_3SdukxAns9lY52GQ1ELZX5v0',
      'refunded' => false,
      'refunds' => 
      array (
        'object' => 'list',
        'data' => 
        array (
        ),
        'has_more' => false,
        'total_count' => 0,
        'url' => '/v1/application_fees/fee_1SdvGlArGwCSIIvewX202xP5/refunds',
      ),
    ),
    'created_at' => '2026-01-07 19:13:32',
    'updated_at' => '2026-01-07 20:47:44',
  ),
  91 => 
  array (
    'id' => 92,
    'stripe_fee_id' => 'fee_1SduQyPEpkzElSu4NLHG7YKd',
    'amount' => '2.93',
    'currency' => 'EUR',
    'created_at_stripe' => '2026-01-07 21:47:44',
    'stripe_account_id' => 'acct_1OrHzGPEpkzElSu4',
    'partner_email' => 'andreadellomodarme@gmail.com',
    'partner_name' => 'PUNTO P DI ANDREA DELL\'OMODARME',
    'client_id' => 383,
    'charge_id' => 'py_1SduQxPEpkzElSu483KI6bfK',
    'description' => 'andreadellomodarme@gmail.com - acct_1OrHzGPEpkzElSu4',
    'period_month' => '2025-12',
    'raw_data' => 
    array (
      'id' => 'fee_1SduQyPEpkzElSu4NLHG7YKd',
      'object' => 'application_fee',
      'account' => 'acct_1OrHzGPEpkzElSu4',
      'amount' => 293,
      'amount_refunded' => 0,
      'application' => 'ca_Ox2Rdo90ojLoUICobfcdmeRJeF2Icbzs',
      'balance_transaction' => 'txn_1SduR0Ans9lY52GQEYYiEz0F',
      'charge' => 'py_1SduQxPEpkzElSu483KI6bfK',
      'created' => 1765638516,
      'currency' => 'eur',
      'fee_source' => 
      array (
        'charge' => 'py_1SduQxPEpkzElSu483KI6bfK',
        'type' => 'charge',
      ),
      'livemode' => true,
      'originating_transaction' => 'ch_3SduMjAns9lY52GQ1UjS3BD2',
      'refunded' => false,
      'refunds' => 
      array (
        'object' => 'list',
        'data' => 
        array (
        ),
        'has_more' => false,
        'total_count' => 0,
        'url' => '/v1/application_fees/fee_1SduQyPEpkzElSu4NLHG7YKd/refunds',
      ),
    ),
    'created_at' => '2026-01-07 19:13:32',
    'updated_at' => '2026-01-07 20:47:44',
  ),
  92 => 
  array (
    'id' => 93,
    'stripe_fee_id' => 'fee_1Sdu0uPEpkzElSu43lO7LTOm',
    'amount' => '2.94',
    'currency' => 'EUR',
    'created_at_stripe' => '2026-01-07 21:47:44',
    'stripe_account_id' => 'acct_1OrHzGPEpkzElSu4',
    'partner_email' => 'andreadellomodarme@gmail.com',
    'partner_name' => 'PUNTO P DI ANDREA DELL\'OMODARME',
    'client_id' => 383,
    'charge_id' => 'py_1Sdu0uPEpkzElSu4NpBOQ6PT',
    'description' => 'andreadellomodarme@gmail.com - acct_1OrHzGPEpkzElSu4',
    'period_month' => '2025-12',
    'raw_data' => 
    array (
      'id' => 'fee_1Sdu0uPEpkzElSu43lO7LTOm',
      'object' => 'application_fee',
      'account' => 'acct_1OrHzGPEpkzElSu4',
      'amount' => 294,
      'amount_refunded' => 0,
      'application' => 'ca_Ox2Rdo90ojLoUICobfcdmeRJeF2Icbzs',
      'balance_transaction' => 'txn_1Sdu0wAns9lY52GQ5cb7JaFX',
      'charge' => 'py_1Sdu0uPEpkzElSu4NpBOQ6PT',
      'created' => 1765636900,
      'currency' => 'eur',
      'fee_source' => 
      array (
        'charge' => 'py_1Sdu0uPEpkzElSu4NpBOQ6PT',
        'type' => 'charge',
      ),
      'livemode' => true,
      'originating_transaction' => 'ch_3SdtusAns9lY52GQ1b1DQ3SO',
      'refunded' => false,
      'refunds' => 
      array (
        'object' => 'list',
        'data' => 
        array (
        ),
        'has_more' => false,
        'total_count' => 0,
        'url' => '/v1/application_fees/fee_1Sdu0uPEpkzElSu43lO7LTOm/refunds',
      ),
    ),
    'created_at' => '2026-01-07 19:13:33',
    'updated_at' => '2026-01-07 20:47:44',
  ),
  93 => 
  array (
    'id' => 94,
    'stripe_fee_id' => 'fee_1Sdd5yPNlXDSKQBIFlwb7rll',
    'amount' => '3.13',
    'currency' => 'EUR',
    'created_at_stripe' => '2026-01-07 21:47:44',
    'stripe_account_id' => 'acct_1QgqazPNlXDSKQBI',
    'partner_email' => 'amministrazione@incarne.it',
    'partner_name' => 'INCARNE SRL',
    'client_id' => 317,
    'charge_id' => 'py_1Sdd5yPNlXDSKQBI78BwBl9H',
    'description' => 'amministrazione@incarne.it - acct_1QgqazPNlXDSKQBI',
    'period_month' => '2025-12',
    'raw_data' => 
    array (
      'id' => 'fee_1Sdd5yPNlXDSKQBIFlwb7rll',
      'object' => 'application_fee',
      'account' => 'acct_1QgqazPNlXDSKQBI',
      'amount' => 313,
      'amount_refunded' => 0,
      'application' => 'ca_Ox2Rdo90ojLoUICobfcdmeRJeF2Icbzs',
      'balance_transaction' => 'txn_1Sdd60Ans9lY52GQFn36g9CC',
      'charge' => 'py_1Sdd5yPNlXDSKQBI78BwBl9H',
      'created' => 1765571866,
      'currency' => 'eur',
      'fee_source' => 
      array (
        'charge' => 'py_1Sdd5yPNlXDSKQBI78BwBl9H',
        'type' => 'charge',
      ),
      'livemode' => true,
      'originating_transaction' => 'ch_3SdcJjAns9lY52GQ1Jzse3fK',
      'refunded' => false,
      'refunds' => 
      array (
        'object' => 'list',
        'data' => 
        array (
        ),
        'has_more' => false,
        'total_count' => 0,
        'url' => '/v1/application_fees/fee_1Sdd5yPNlXDSKQBIFlwb7rll/refunds',
      ),
    ),
    'created_at' => '2026-01-07 19:13:34',
    'updated_at' => '2026-01-07 20:47:44',
  ),
  94 => 
  array (
    'id' => 95,
    'stripe_fee_id' => 'fee_1SdbG7AcaExTZKe8ZJTQRCnh',
    'amount' => '4.05',
    'currency' => 'EUR',
    'created_at_stripe' => '2026-01-07 21:47:44',
    'stripe_account_id' => 'acct_1QsMHWAcaExTZKe8',
    'partner_email' => 'nclcucc@gmail.com',
    'partner_name' => 'La Loggia sul Mare SRL',
    'client_id' => 322,
    'charge_id' => 'py_1SdbG7AcaExTZKe8lOLQGiww',
    'description' => 'nclcucc@gmail.com - acct_1QsMHWAcaExTZKe8',
    'period_month' => '2025-12',
    'raw_data' => 
    array (
      'id' => 'fee_1SdbG7AcaExTZKe8ZJTQRCnh',
      'object' => 'application_fee',
      'account' => 'acct_1QsMHWAcaExTZKe8',
      'amount' => 405,
      'amount_refunded' => 0,
      'application' => 'ca_Ox2Rdo90ojLoUICobfcdmeRJeF2Icbzs',
      'balance_transaction' => 'txn_1SdbG9Ans9lY52GQhZ7CVrjo',
      'charge' => 'py_1SdbG7AcaExTZKe8lOLQGiww',
      'created' => 1765564807,
      'currency' => 'eur',
      'fee_source' => 
      array (
        'charge' => 'py_1SdbG7AcaExTZKe8lOLQGiww',
        'type' => 'charge',
      ),
      'livemode' => true,
      'originating_transaction' => 'ch_3SdaHoAns9lY52GQ0y0lTM69',
      'refunded' => false,
      'refunds' => 
      array (
        'object' => 'list',
        'data' => 
        array (
        ),
        'has_more' => false,
        'total_count' => 0,
        'url' => '/v1/application_fees/fee_1SdbG7AcaExTZKe8ZJTQRCnh/refunds',
      ),
    ),
    'created_at' => '2026-01-07 19:13:34',
    'updated_at' => '2026-01-07 20:47:44',
  ),
  95 => 
  array (
    'id' => 96,
    'stripe_fee_id' => 'fee_1SdZzQPB7qjhlfVaGWXO3gZq',
    'amount' => '3.01',
    'currency' => 'EUR',
    'created_at_stripe' => '2026-01-07 21:47:44',
    'stripe_account_id' => 'acct_1OrlPGPB7qjhlfVa',
    'partner_email' => 'laboratoriodellapizza@gmail.com',
    'partner_name' => 'Macrì Domenico',
    'client_id' => 325,
    'charge_id' => 'py_1SdZzQPB7qjhlfVaE7NWT1fM',
    'description' => 'laboratoriodellapizza@gmail.com - acct_1OrlPGPB7qjhlfVa',
    'period_month' => '2025-12',
    'raw_data' => 
    array (
      'id' => 'fee_1SdZzQPB7qjhlfVaGWXO3gZq',
      'object' => 'application_fee',
      'account' => 'acct_1OrlPGPB7qjhlfVa',
      'amount' => 301,
      'amount_refunded' => 0,
      'application' => 'ca_Ox2Rdo90ojLoUICobfcdmeRJeF2Icbzs',
      'balance_transaction' => 'txn_1SdZzSAns9lY52GQhR5PjhfL',
      'charge' => 'py_1SdZzQPB7qjhlfVaE7NWT1fM',
      'created' => 1765559928,
      'currency' => 'eur',
      'fee_source' => 
      array (
        'charge' => 'py_1SdZzQPB7qjhlfVaE7NWT1fM',
        'type' => 'charge',
      ),
      'livemode' => true,
      'originating_transaction' => 'ch_3SdZyzAns9lY52GQ0yNEDnFb',
      'refunded' => false,
      'refunds' => 
      array (
        'object' => 'list',
        'data' => 
        array (
        ),
        'has_more' => false,
        'total_count' => 0,
        'url' => '/v1/application_fees/fee_1SdZzQPB7qjhlfVaGWXO3gZq/refunds',
      ),
    ),
    'created_at' => '2026-01-07 19:13:35',
    'updated_at' => '2026-01-07 20:47:44',
  ),
  96 => 
  array (
    'id' => 97,
    'stripe_fee_id' => 'fee_1SdYptPEpkzElSu4eKxFHdlG',
    'amount' => '2.84',
    'currency' => 'EUR',
    'created_at_stripe' => '2026-01-07 21:47:44',
    'stripe_account_id' => 'acct_1OrHzGPEpkzElSu4',
    'partner_email' => 'andreadellomodarme@gmail.com',
    'partner_name' => 'PUNTO P DI ANDREA DELL\'OMODARME',
    'client_id' => 383,
    'charge_id' => 'py_1SdYptPEpkzElSu4CaE9u3lV',
    'description' => 'andreadellomodarme@gmail.com - acct_1OrHzGPEpkzElSu4',
    'period_month' => '2025-12',
    'raw_data' => 
    array (
      'id' => 'fee_1SdYptPEpkzElSu4eKxFHdlG',
      'object' => 'application_fee',
      'account' => 'acct_1OrHzGPEpkzElSu4',
      'amount' => 284,
      'amount_refunded' => 0,
      'application' => 'ca_Ox2Rdo90ojLoUICobfcdmeRJeF2Icbzs',
      'balance_transaction' => 'txn_1SdYpwAns9lY52GQ7oMIzJ47',
      'charge' => 'py_1SdYptPEpkzElSu4CaE9u3lV',
      'created' => 1765555493,
      'currency' => 'eur',
      'fee_source' => 
      array (
        'charge' => 'py_1SdYptPEpkzElSu4CaE9u3lV',
        'type' => 'charge',
      ),
      'livemode' => true,
      'originating_transaction' => 'ch_3SdYb7Ans9lY52GQ1CwzH5v3',
      'refunded' => false,
      'refunds' => 
      array (
        'object' => 'list',
        'data' => 
        array (
        ),
        'has_more' => false,
        'total_count' => 0,
        'url' => '/v1/application_fees/fee_1SdYptPEpkzElSu4eKxFHdlG/refunds',
      ),
    ),
    'created_at' => '2026-01-07 19:13:35',
    'updated_at' => '2026-01-07 20:47:44',
  ),
  97 => 
  array (
    'id' => 98,
    'stripe_fee_id' => 'fee_1SdXZAPEpkzElSu446H5OGnH',
    'amount' => '3.58',
    'currency' => 'EUR',
    'created_at_stripe' => '2026-01-07 21:47:44',
    'stripe_account_id' => 'acct_1OrHzGPEpkzElSu4',
    'partner_email' => 'andreadellomodarme@gmail.com',
    'partner_name' => 'PUNTO P DI ANDREA DELL\'OMODARME',
    'client_id' => 383,
    'charge_id' => 'py_1SdXZAPEpkzElSu4jsVcxUXP',
    'description' => 'andreadellomodarme@gmail.com - acct_1OrHzGPEpkzElSu4',
    'period_month' => '2025-12',
    'raw_data' => 
    array (
      'id' => 'fee_1SdXZAPEpkzElSu446H5OGnH',
      'object' => 'application_fee',
      'account' => 'acct_1OrHzGPEpkzElSu4',
      'amount' => 358,
      'amount_refunded' => 0,
      'application' => 'ca_Ox2Rdo90ojLoUICobfcdmeRJeF2Icbzs',
      'balance_transaction' => 'txn_1SdXZDAns9lY52GQBEVakdun',
      'charge' => 'py_1SdXZAPEpkzElSu4jsVcxUXP',
      'created' => 1765550612,
      'currency' => 'eur',
      'fee_source' => 
      array (
        'charge' => 'py_1SdXZAPEpkzElSu4jsVcxUXP',
        'type' => 'charge',
      ),
      'livemode' => true,
      'originating_transaction' => 'ch_3SdTLpAns9lY52GQ1gwh1PtX',
      'refunded' => false,
      'refunds' => 
      array (
        'object' => 'list',
        'data' => 
        array (
        ),
        'has_more' => false,
        'total_count' => 0,
        'url' => '/v1/application_fees/fee_1SdXZAPEpkzElSu446H5OGnH/refunds',
      ),
    ),
    'created_at' => '2026-01-07 19:13:36',
    'updated_at' => '2026-01-07 20:47:44',
  ),
  98 => 
  array (
    'id' => 99,
    'stripe_fee_id' => 'fee_1SdVfkArGwCSIIveTeaHeo9J',
    'amount' => '2.75',
    'currency' => 'EUR',
    'created_at_stripe' => '2026-01-07 21:47:44',
    'stripe_account_id' => 'acct_1SRyIkArGwCSIIve',
    'partner_email' => 'ordinazioni@sbriciolopizza.it',
    'partner_name' => 'PACIFIC JAFFE S.R.L.',
    'client_id' => 333,
    'charge_id' => 'py_1SdVfkArGwCSIIveHZY8S5RH',
    'description' => 'ordinazioni@sbriciolopizza.it - acct_1SRyIkArGwCSIIve',
    'period_month' => '2025-12',
    'raw_data' => 
    array (
      'id' => 'fee_1SdVfkArGwCSIIveTeaHeo9J',
      'object' => 'application_fee',
      'account' => 'acct_1SRyIkArGwCSIIve',
      'amount' => 275,
      'amount_refunded' => 0,
      'application' => 'ca_Ox2Rdo90ojLoUICobfcdmeRJeF2Icbzs',
      'balance_transaction' => 'txn_1SdVfmAns9lY52GQtQ3VFFSz',
      'charge' => 'py_1SdVfkArGwCSIIveHZY8S5RH',
      'created' => 1765543332,
      'currency' => 'eur',
      'fee_source' => 
      array (
        'charge' => 'py_1SdVfkArGwCSIIveHZY8S5RH',
        'type' => 'charge',
      ),
      'livemode' => true,
      'originating_transaction' => 'ch_3SdVKBAns9lY52GQ0UrXqLeT',
      'refunded' => false,
      'refunds' => 
      array (
        'object' => 'list',
        'data' => 
        array (
        ),
        'has_more' => false,
        'total_count' => 0,
        'url' => '/v1/application_fees/fee_1SdVfkArGwCSIIveTeaHeo9J/refunds',
      ),
    ),
    'created_at' => '2026-01-07 19:13:37',
    'updated_at' => '2026-01-07 20:47:44',
  ),
  99 => 
  array (
    'id' => 100,
    'stripe_fee_id' => 'fee_1SdFlBAcaExTZKe8rDiSFgmD',
    'amount' => '3.74',
    'currency' => 'EUR',
    'created_at_stripe' => '2026-01-07 21:47:44',
    'stripe_account_id' => 'acct_1QsMHWAcaExTZKe8',
    'partner_email' => 'nclcucc@gmail.com',
    'partner_name' => 'La Loggia sul Mare SRL',
    'client_id' => 322,
    'charge_id' => 'py_1SdFlBAcaExTZKe80kN16E1z',
    'description' => 'nclcucc@gmail.com - acct_1QsMHWAcaExTZKe8',
    'period_month' => '2025-12',
    'raw_data' => 
    array (
      'id' => 'fee_1SdFlBAcaExTZKe8rDiSFgmD',
      'object' => 'application_fee',
      'account' => 'acct_1QsMHWAcaExTZKe8',
      'amount' => 374,
      'amount_refunded' => 0,
      'application' => 'ca_Ox2Rdo90ojLoUICobfcdmeRJeF2Icbzs',
      'balance_transaction' => 'txn_1SdFlDAns9lY52GQIVOWFwOK',
      'charge' => 'py_1SdFlBAcaExTZKe80kN16E1z',
      'created' => 1765482165,
      'currency' => 'eur',
      'fee_source' => 
      array (
        'charge' => 'py_1SdFlBAcaExTZKe80kN16E1z',
        'type' => 'charge',
      ),
      'livemode' => true,
      'originating_transaction' => 'ch_3SdFbLAns9lY52GQ0E2b1gmq',
      'refunded' => false,
      'refunds' => 
      array (
        'object' => 'list',
        'data' => 
        array (
        ),
        'has_more' => false,
        'total_count' => 0,
        'url' => '/v1/application_fees/fee_1SdFlBAcaExTZKe8rDiSFgmD/refunds',
      ),
    ),
    'created_at' => '2026-01-07 19:13:37',
    'updated_at' => '2026-01-07 20:47:44',
  ),
  100 => 
  array (
    'id' => 101,
    'stripe_fee_id' => 'fee_1SdEGoPB7qjhlfVaDs2YtsVT',
    'amount' => '2.95',
    'currency' => 'EUR',
    'created_at_stripe' => '2026-01-07 21:47:44',
    'stripe_account_id' => 'acct_1OrlPGPB7qjhlfVa',
    'partner_email' => 'laboratoriodellapizza@gmail.com',
    'partner_name' => 'Macrì Domenico',
    'client_id' => 325,
    'charge_id' => 'py_1SdEGoPB7qjhlfVadXvCNqE2',
    'description' => 'laboratoriodellapizza@gmail.com - acct_1OrlPGPB7qjhlfVa',
    'period_month' => '2025-12',
    'raw_data' => 
    array (
      'id' => 'fee_1SdEGoPB7qjhlfVaDs2YtsVT',
      'object' => 'application_fee',
      'account' => 'acct_1OrlPGPB7qjhlfVa',
      'amount' => 295,
      'amount_refunded' => 0,
      'application' => 'ca_Ox2Rdo90ojLoUICobfcdmeRJeF2Icbzs',
      'balance_transaction' => 'txn_1SdEGqAns9lY52GQ11XhA5ge',
      'charge' => 'py_1SdEGoPB7qjhlfVadXvCNqE2',
      'created' => 1765476438,
      'currency' => 'eur',
      'fee_source' => 
      array (
        'charge' => 'py_1SdEGoPB7qjhlfVadXvCNqE2',
        'type' => 'charge',
      ),
      'livemode' => true,
      'originating_transaction' => 'ch_3SdEEnAns9lY52GQ0xuWIV53',
      'refunded' => false,
      'refunds' => 
      array (
        'object' => 'list',
        'data' => 
        array (
        ),
        'has_more' => false,
        'total_count' => 0,
        'url' => '/v1/application_fees/fee_1SdEGoPB7qjhlfVaDs2YtsVT/refunds',
      ),
    ),
    'created_at' => '2026-01-07 19:13:39',
    'updated_at' => '2026-01-07 20:47:44',
  ),
  101 => 
  array (
    'id' => 102,
    'stripe_fee_id' => 'fee_1SdE0HPEpkzElSu4ScDTj7M6',
    'amount' => '3.04',
    'currency' => 'EUR',
    'created_at_stripe' => '2026-01-07 21:47:44',
    'stripe_account_id' => 'acct_1OrHzGPEpkzElSu4',
    'partner_email' => 'andreadellomodarme@gmail.com',
    'partner_name' => 'PUNTO P DI ANDREA DELL\'OMODARME',
    'client_id' => 383,
    'charge_id' => 'py_1SdE0HPEpkzElSu422c98DzJ',
    'description' => 'andreadellomodarme@gmail.com - acct_1OrHzGPEpkzElSu4',
    'period_month' => '2025-12',
    'raw_data' => 
    array (
      'id' => 'fee_1SdE0HPEpkzElSu4ScDTj7M6',
      'object' => 'application_fee',
      'account' => 'acct_1OrHzGPEpkzElSu4',
      'amount' => 304,
      'amount_refunded' => 0,
      'application' => 'ca_Ox2Rdo90ojLoUICobfcdmeRJeF2Icbzs',
      'balance_transaction' => 'txn_1SdE0JAns9lY52GQAbfvfKNo',
      'charge' => 'py_1SdE0HPEpkzElSu422c98DzJ',
      'created' => 1765475413,
      'currency' => 'eur',
      'fee_source' => 
      array (
        'charge' => 'py_1SdE0HPEpkzElSu422c98DzJ',
        'type' => 'charge',
      ),
      'livemode' => true,
      'originating_transaction' => 'ch_3SdDzzAns9lY52GQ0zTPZuTP',
      'refunded' => false,
      'refunds' => 
      array (
        'object' => 'list',
        'data' => 
        array (
        ),
        'has_more' => false,
        'total_count' => 0,
        'url' => '/v1/application_fees/fee_1SdE0HPEpkzElSu4ScDTj7M6/refunds',
      ),
    ),
    'created_at' => '2026-01-07 19:13:40',
    'updated_at' => '2026-01-07 20:47:44',
  ),
  102 => 
  array (
    'id' => 103,
    'stripe_fee_id' => 'fee_1Sct0FPB7qjhlfVan2x5DG8k',
    'amount' => '3.19',
    'currency' => 'EUR',
    'created_at_stripe' => '2026-01-07 21:47:44',
    'stripe_account_id' => 'acct_1OrlPGPB7qjhlfVa',
    'partner_email' => 'laboratoriodellapizza@gmail.com',
    'partner_name' => 'Macrì Domenico',
    'client_id' => 325,
    'charge_id' => 'py_1Sct0FPB7qjhlfVaIBwznyQB',
    'description' => 'laboratoriodellapizza@gmail.com - acct_1OrlPGPB7qjhlfVa',
    'period_month' => '2025-12',
    'raw_data' => 
    array (
      'id' => 'fee_1Sct0FPB7qjhlfVan2x5DG8k',
      'object' => 'application_fee',
      'account' => 'acct_1OrlPGPB7qjhlfVa',
      'amount' => 319,
      'amount_refunded' => 0,
      'application' => 'ca_Ox2Rdo90ojLoUICobfcdmeRJeF2Icbzs',
      'balance_transaction' => 'txn_1Sct0HAns9lY52GQUbkugV4U',
      'charge' => 'py_1Sct0FPB7qjhlfVaIBwznyQB',
      'created' => 1765394687,
      'currency' => 'eur',
      'fee_source' => 
      array (
        'charge' => 'py_1Sct0FPB7qjhlfVaIBwznyQB',
        'type' => 'charge',
      ),
      'livemode' => true,
      'originating_transaction' => 'ch_3ScszbAns9lY52GQ0q8LJlcb',
      'refunded' => false,
      'refunds' => 
      array (
        'object' => 'list',
        'data' => 
        array (
        ),
        'has_more' => false,
        'total_count' => 0,
        'url' => '/v1/application_fees/fee_1Sct0FPB7qjhlfVan2x5DG8k/refunds',
      ),
    ),
    'created_at' => '2026-01-07 19:13:41',
    'updated_at' => '2026-01-07 20:47:44',
  ),
  103 => 
  array (
    'id' => 104,
    'stripe_fee_id' => 'fee_1Scs7ZPIzlXORG3aejLssfIu',
    'amount' => '4.72',
    'currency' => 'EUR',
    'created_at_stripe' => '2026-01-07 21:47:44',
    'stripe_account_id' => 'acct_1OYpemPIzlXORG3a',
    'partner_email' => 'feusrl.2019@gmail.com',
    'partner_name' => 'Feu Srl',
    'client_id' => 301,
    'charge_id' => 'py_1Scs7ZPIzlXORG3a3EjsUME1',
    'description' => 'feusrl.2019@gmail.com - acct_1OYpemPIzlXORG3a',
    'period_month' => '2025-12',
    'raw_data' => 
    array (
      'id' => 'fee_1Scs7ZPIzlXORG3aejLssfIu',
      'object' => 'application_fee',
      'account' => 'acct_1OYpemPIzlXORG3a',
      'amount' => 472,
      'amount_refunded' => 0,
      'application' => 'ca_Ox2Rdo90ojLoUICobfcdmeRJeF2Icbzs',
      'balance_transaction' => 'txn_1Scs7cAns9lY52GQv8tPhoQW',
      'charge' => 'py_1Scs7ZPIzlXORG3a3EjsUME1',
      'created' => 1765391297,
      'currency' => 'eur',
      'fee_source' => 
      array (
        'charge' => 'py_1Scs7ZPIzlXORG3a3EjsUME1',
        'type' => 'charge',
      ),
      'livemode' => true,
      'originating_transaction' => 'ch_3Scs5VAns9lY52GQ0Xm0M1Vq',
      'refunded' => false,
      'refunds' => 
      array (
        'object' => 'list',
        'data' => 
        array (
        ),
        'has_more' => false,
        'total_count' => 0,
        'url' => '/v1/application_fees/fee_1Scs7ZPIzlXORG3aejLssfIu/refunds',
      ),
    ),
    'created_at' => '2026-01-07 19:13:41',
    'updated_at' => '2026-01-07 20:47:44',
  ),
  104 => 
  array (
    'id' => 105,
    'stripe_fee_id' => 'fee_1Scs6QPNB3k6tHL8dOOvRdPT',
    'amount' => '3.86',
    'currency' => 'EUR',
    'created_at_stripe' => '2026-01-07 21:47:44',
    'stripe_account_id' => 'acct_1RYoXnPNB3k6tHL8',
    'partner_email' => 'pizzeriaitrecanti@gmail.com',
    'partner_name' => 'Pizzeria I 3 Canti di Bibbiani Mirco & C. SAS',
    'client_id' => 340,
    'charge_id' => 'py_1Scs6QPNB3k6tHL82ylrh8LJ',
    'description' => 'pizzeriaitrecanti@gmail.com - acct_1RYoXnPNB3k6tHL8',
    'period_month' => '2025-12',
    'raw_data' => 
    array (
      'id' => 'fee_1Scs6QPNB3k6tHL8dOOvRdPT',
      'object' => 'application_fee',
      'account' => 'acct_1RYoXnPNB3k6tHL8',
      'amount' => 386,
      'amount_refunded' => 0,
      'application' => 'ca_Ox2Rdo90ojLoUICobfcdmeRJeF2Icbzs',
      'balance_transaction' => 'txn_1Scs6SAns9lY52GQ4pceLE44',
      'charge' => 'py_1Scs6QPNB3k6tHL82ylrh8LJ',
      'created' => 1765391226,
      'currency' => 'eur',
      'fee_source' => 
      array (
        'charge' => 'py_1Scs6QPNB3k6tHL82ylrh8LJ',
        'type' => 'charge',
      ),
      'livemode' => true,
      'originating_transaction' => 'ch_3ScrndAns9lY52GQ0Ce4cici',
      'refunded' => false,
      'refunds' => 
      array (
        'object' => 'list',
        'data' => 
        array (
        ),
        'has_more' => false,
        'total_count' => 0,
        'url' => '/v1/application_fees/fee_1Scs6QPNB3k6tHL8dOOvRdPT/refunds',
      ),
    ),
    'created_at' => '2026-01-07 19:13:42',
    'updated_at' => '2026-01-07 20:47:44',
  ),
  105 => 
  array (
    'id' => 106,
    'stripe_fee_id' => 'fee_1ScqzEPAESt8veHwrpu19SYG',
    'amount' => '2.73',
    'currency' => 'EUR',
    'created_at_stripe' => '2026-01-07 21:47:44',
    'stripe_account_id' => 'acct_1ROCdFPAESt8veHw',
    'partner_email' => 'anticatradizione1950@gmail.com',
    'partner_name' => 'Osteria Antica Tradizione srls.',
    'client_id' => 332,
    'charge_id' => 'py_1ScqzEPAESt8veHwTotx9rRa',
    'description' => 'anticatradizione1950@gmail.com - acct_1ROCdFPAESt8veHw',
    'period_month' => '2025-12',
    'raw_data' => 
    array (
      'id' => 'fee_1ScqzEPAESt8veHwrpu19SYG',
      'object' => 'application_fee',
      'account' => 'acct_1ROCdFPAESt8veHw',
      'amount' => 273,
      'amount_refunded' => 0,
      'application' => 'ca_Ox2Rdo90ojLoUICobfcdmeRJeF2Icbzs',
      'balance_transaction' => 'txn_1ScqzHAns9lY52GQh4wQ3cDj',
      'charge' => 'py_1ScqzEPAESt8veHwTotx9rRa',
      'created' => 1765386936,
      'currency' => 'eur',
      'fee_source' => 
      array (
        'charge' => 'py_1ScqzEPAESt8veHwTotx9rRa',
        'type' => 'charge',
      ),
      'livemode' => true,
      'originating_transaction' => 'ch_3ScqyYAns9lY52GQ1VqSYhOk',
      'refunded' => false,
      'refunds' => 
      array (
        'object' => 'list',
        'data' => 
        array (
        ),
        'has_more' => false,
        'total_count' => 0,
        'url' => '/v1/application_fees/fee_1ScqzEPAESt8veHwrpu19SYG/refunds',
      ),
    ),
    'created_at' => '2026-01-07 19:13:43',
    'updated_at' => '2026-01-07 20:47:44',
  ),
  106 => 
  array (
    'id' => 107,
    'stripe_fee_id' => 'fee_1ScpojPEpkzElSu4t1Qu4xvr',
    'amount' => '2.88',
    'currency' => 'EUR',
    'created_at_stripe' => '2026-01-07 21:47:44',
    'stripe_account_id' => 'acct_1OrHzGPEpkzElSu4',
    'partner_email' => 'andreadellomodarme@gmail.com',
    'partner_name' => 'PUNTO P DI ANDREA DELL\'OMODARME',
    'client_id' => 383,
    'charge_id' => 'py_1ScpojPEpkzElSu4xlACJAXs',
    'description' => 'andreadellomodarme@gmail.com - acct_1OrHzGPEpkzElSu4',
    'period_month' => '2025-12',
    'raw_data' => 
    array (
      'id' => 'fee_1ScpojPEpkzElSu4t1Qu4xvr',
      'object' => 'application_fee',
      'account' => 'acct_1OrHzGPEpkzElSu4',
      'amount' => 288,
      'amount_refunded' => 0,
      'application' => 'ca_Ox2Rdo90ojLoUICobfcdmeRJeF2Icbzs',
      'balance_transaction' => 'txn_1ScpolAns9lY52GQboRo3UZW',
      'charge' => 'py_1ScpojPEpkzElSu4xlACJAXs',
      'created' => 1765382441,
      'currency' => 'eur',
      'fee_source' => 
      array (
        'charge' => 'py_1ScpojPEpkzElSu4xlACJAXs',
        'type' => 'charge',
      ),
      'livemode' => true,
      'originating_transaction' => 'ch_3ScpkBAns9lY52GQ1Gkiex6A',
      'refunded' => false,
      'refunds' => 
      array (
        'object' => 'list',
        'data' => 
        array (
        ),
        'has_more' => false,
        'total_count' => 0,
        'url' => '/v1/application_fees/fee_1ScpojPEpkzElSu4t1Qu4xvr/refunds',
      ),
    ),
    'created_at' => '2026-01-07 19:13:43',
    'updated_at' => '2026-01-07 20:47:44',
  ),
  107 => 
  array (
    'id' => 108,
    'stripe_fee_id' => 'fee_1ScpngPEpkzElSu4yuMF2Fzy',
    'amount' => '2.92',
    'currency' => 'EUR',
    'created_at_stripe' => '2026-01-07 21:47:44',
    'stripe_account_id' => 'acct_1OrHzGPEpkzElSu4',
    'partner_email' => 'andreadellomodarme@gmail.com',
    'partner_name' => 'PUNTO P DI ANDREA DELL\'OMODARME',
    'client_id' => 383,
    'charge_id' => 'py_1ScpngPEpkzElSu4nxrcFP7l',
    'description' => 'andreadellomodarme@gmail.com - acct_1OrHzGPEpkzElSu4',
    'period_month' => '2025-12',
    'raw_data' => 
    array (
      'id' => 'fee_1ScpngPEpkzElSu4yuMF2Fzy',
      'object' => 'application_fee',
      'account' => 'acct_1OrHzGPEpkzElSu4',
      'amount' => 292,
      'amount_refunded' => 0,
      'application' => 'ca_Ox2Rdo90ojLoUICobfcdmeRJeF2Icbzs',
      'balance_transaction' => 'txn_1ScpnjAns9lY52GQf9BUKFNW',
      'charge' => 'py_1ScpngPEpkzElSu4nxrcFP7l',
      'created' => 1765382376,
      'currency' => 'eur',
      'fee_source' => 
      array (
        'charge' => 'py_1ScpngPEpkzElSu4nxrcFP7l',
        'type' => 'charge',
      ),
      'livemode' => true,
      'originating_transaction' => 'ch_3ScmwaAns9lY52GQ1M8hk7pJ',
      'refunded' => false,
      'refunds' => 
      array (
        'object' => 'list',
        'data' => 
        array (
        ),
        'has_more' => false,
        'total_count' => 0,
        'url' => '/v1/application_fees/fee_1ScpngPEpkzElSu4yuMF2Fzy/refunds',
      ),
    ),
    'created_at' => '2026-01-07 19:13:44',
    'updated_at' => '2026-01-07 20:47:44',
  ),
  108 => 
  array (
    'id' => 109,
    'stripe_fee_id' => 'fee_1SclVxPIzlXORG3atveYDYYa',
    'amount' => '2.84',
    'currency' => 'EUR',
    'created_at_stripe' => '2026-01-07 21:47:44',
    'stripe_account_id' => 'acct_1OYpemPIzlXORG3a',
    'partner_email' => 'feusrl.2019@gmail.com',
    'partner_name' => 'Feu Srl',
    'client_id' => 301,
    'charge_id' => 'py_1SclVxPIzlXORG3aLMeIGogX',
    'description' => 'feusrl.2019@gmail.com - acct_1OYpemPIzlXORG3a',
    'period_month' => '2025-12',
    'raw_data' => 
    array (
      'id' => 'fee_1SclVxPIzlXORG3atveYDYYa',
      'object' => 'application_fee',
      'account' => 'acct_1OYpemPIzlXORG3a',
      'amount' => 284,
      'amount_refunded' => 0,
      'application' => 'ca_Ox2Rdo90ojLoUICobfcdmeRJeF2Icbzs',
      'balance_transaction' => 'txn_1SclW0Ans9lY52GQnSQ4AFtv',
      'charge' => 'py_1SclVxPIzlXORG3aLMeIGogX',
      'created' => 1765365901,
      'currency' => 'eur',
      'fee_source' => 
      array (
        'charge' => 'py_1SclVxPIzlXORG3aLMeIGogX',
        'type' => 'charge',
      ),
      'livemode' => true,
      'originating_transaction' => 'ch_3ScjUdAns9lY52GQ1pTeaXEW',
      'refunded' => false,
      'refunds' => 
      array (
        'object' => 'list',
        'data' => 
        array (
        ),
        'has_more' => false,
        'total_count' => 0,
        'url' => '/v1/application_fees/fee_1SclVxPIzlXORG3atveYDYYa/refunds',
      ),
    ),
    'created_at' => '2026-01-07 19:13:44',
    'updated_at' => '2026-01-07 20:47:44',
  ),
  109 => 
  array (
    'id' => 110,
    'stripe_fee_id' => 'fee_1SclJjPIzlXORG3a3fk2KCsh',
    'amount' => '5.74',
    'currency' => 'EUR',
    'created_at_stripe' => '2026-01-07 21:47:44',
    'stripe_account_id' => 'acct_1OYpemPIzlXORG3a',
    'partner_email' => 'feusrl.2019@gmail.com',
    'partner_name' => 'Feu Srl',
    'client_id' => 301,
    'charge_id' => 'py_1SclJjPIzlXORG3ayZS153ac',
    'description' => 'feusrl.2019@gmail.com - acct_1OYpemPIzlXORG3a',
    'period_month' => '2025-12',
    'raw_data' => 
    array (
      'id' => 'fee_1SclJjPIzlXORG3a3fk2KCsh',
      'object' => 'application_fee',
      'account' => 'acct_1OYpemPIzlXORG3a',
      'amount' => 574,
      'amount_refunded' => 0,
      'application' => 'ca_Ox2Rdo90ojLoUICobfcdmeRJeF2Icbzs',
      'balance_transaction' => 'txn_1SclJmAns9lY52GQMI5AgyM8',
      'charge' => 'py_1SclJjPIzlXORG3ayZS153ac',
      'created' => 1765365143,
      'currency' => 'eur',
      'fee_source' => 
      array (
        'charge' => 'py_1SclJjPIzlXORG3ayZS153ac',
        'type' => 'charge',
      ),
      'livemode' => true,
      'originating_transaction' => 'ch_3Sck08Ans9lY52GQ0xTlfl3M',
      'refunded' => false,
      'refunds' => 
      array (
        'object' => 'list',
        'data' => 
        array (
        ),
        'has_more' => false,
        'total_count' => 0,
        'url' => '/v1/application_fees/fee_1SclJjPIzlXORG3a3fk2KCsh/refunds',
      ),
    ),
    'created_at' => '2026-01-07 19:13:45',
    'updated_at' => '2026-01-07 20:47:44',
  ),
  110 => 
  array (
    'id' => 111,
    'stripe_fee_id' => 'fee_1Sc9IKPIzlXORG3aZs5CjX3P',
    'amount' => '4.66',
    'currency' => 'EUR',
    'created_at_stripe' => '2026-01-07 21:47:44',
    'stripe_account_id' => 'acct_1OYpemPIzlXORG3a',
    'partner_email' => 'feusrl.2019@gmail.com',
    'partner_name' => 'Feu Srl',
    'client_id' => 301,
    'charge_id' => 'py_1Sc9IKPIzlXORG3aFQ7BWY2K',
    'description' => 'feusrl.2019@gmail.com - acct_1OYpemPIzlXORG3a',
    'period_month' => '2025-12',
    'raw_data' => 
    array (
      'id' => 'fee_1Sc9IKPIzlXORG3aZs5CjX3P',
      'object' => 'application_fee',
      'account' => 'acct_1OYpemPIzlXORG3a',
      'amount' => 466,
      'amount_refunded' => 0,
      'application' => 'ca_Ox2Rdo90ojLoUICobfcdmeRJeF2Icbzs',
      'balance_transaction' => 'txn_1Sc9INAns9lY52GQjc5COyfS',
      'charge' => 'py_1Sc9IKPIzlXORG3aFQ7BWY2K',
      'created' => 1765218984,
      'currency' => 'eur',
      'fee_source' => 
      array (
        'charge' => 'py_1Sc9IKPIzlXORG3aFQ7BWY2K',
        'type' => 'charge',
      ),
      'livemode' => true,
      'originating_transaction' => 'ch_3Sc8sgAns9lY52GQ12g1Xh3A',
      'refunded' => false,
      'refunds' => 
      array (
        'object' => 'list',
        'data' => 
        array (
        ),
        'has_more' => false,
        'total_count' => 0,
        'url' => '/v1/application_fees/fee_1Sc9IKPIzlXORG3aZs5CjX3P/refunds',
      ),
    ),
    'created_at' => '2026-01-07 19:13:45',
    'updated_at' => '2026-01-07 20:47:44',
  ),
  111 => 
  array (
    'id' => 112,
    'stripe_fee_id' => 'fee_1Sc76ZPEpkzElSu4tOJ7Tms4',
    'amount' => '4.20',
    'currency' => 'EUR',
    'created_at_stripe' => '2026-01-07 21:47:44',
    'stripe_account_id' => 'acct_1OrHzGPEpkzElSu4',
    'partner_email' => 'andreadellomodarme@gmail.com',
    'partner_name' => 'PUNTO P DI ANDREA DELL\'OMODARME',
    'client_id' => 383,
    'charge_id' => 'py_1Sc76YPEpkzElSu4RFTJ7wXQ',
    'description' => 'andreadellomodarme@gmail.com - acct_1OrHzGPEpkzElSu4',
    'period_month' => '2025-12',
    'raw_data' => 
    array (
      'id' => 'fee_1Sc76ZPEpkzElSu4tOJ7Tms4',
      'object' => 'application_fee',
      'account' => 'acct_1OrHzGPEpkzElSu4',
      'amount' => 420,
      'amount_refunded' => 0,
      'application' => 'ca_Ox2Rdo90ojLoUICobfcdmeRJeF2Icbzs',
      'balance_transaction' => 'txn_1Sc76bAns9lY52GQMD9MARVW',
      'charge' => 'py_1Sc76YPEpkzElSu4RFTJ7wXQ',
      'created' => 1765210567,
      'currency' => 'eur',
      'fee_source' => 
      array (
        'charge' => 'py_1Sc76YPEpkzElSu4RFTJ7wXQ',
        'type' => 'charge',
      ),
      'livemode' => true,
      'originating_transaction' => 'ch_3Sc6XnAns9lY52GQ0qNj0nXG',
      'refunded' => false,
      'refunds' => 
      array (
        'object' => 'list',
        'data' => 
        array (
        ),
        'has_more' => false,
        'total_count' => 0,
        'url' => '/v1/application_fees/fee_1Sc76ZPEpkzElSu4tOJ7Tms4/refunds',
      ),
    ),
    'created_at' => '2026-01-07 19:13:46',
    'updated_at' => '2026-01-07 20:47:44',
  ),
  112 => 
  array (
    'id' => 113,
    'stripe_fee_id' => 'fee_1Sbm4NPFSTNU0nUGQA86GxEr',
    'amount' => '2.77',
    'currency' => 'EUR',
    'created_at_stripe' => '2026-01-07 21:47:44',
    'stripe_account_id' => 'acct_1OtvsmPFSTNU0nUG',
    'partner_email' => 'fortipizza@gmail.com',
    'partner_name' => 'Forti Pizza e Torta',
    'client_id' => 303,
    'charge_id' => 'py_1Sbm4MPFSTNU0nUGnvke3ukC',
    'description' => 'fortipizza@gmail.com - acct_1OtvsmPFSTNU0nUG',
    'period_month' => '2025-12',
    'raw_data' => 
    array (
      'id' => 'fee_1Sbm4NPFSTNU0nUGQA86GxEr',
      'object' => 'application_fee',
      'account' => 'acct_1OtvsmPFSTNU0nUG',
      'amount' => 277,
      'amount_refunded' => 0,
      'application' => 'ca_Ox2Rdo90ojLoUICobfcdmeRJeF2Icbzs',
      'balance_transaction' => 'txn_1Sbm4PAns9lY52GQ6CWSDH1P',
      'charge' => 'py_1Sbm4MPFSTNU0nUGnvke3ukC',
      'created' => 1765129707,
      'currency' => 'eur',
      'fee_source' => 
      array (
        'charge' => 'py_1Sbm4MPFSTNU0nUGnvke3ukC',
        'type' => 'charge',
      ),
      'livemode' => true,
      'originating_transaction' => 'ch_3Sbm3eAns9lY52GQ0U1XXYIl',
      'refunded' => false,
      'refunds' => 
      array (
        'object' => 'list',
        'data' => 
        array (
        ),
        'has_more' => false,
        'total_count' => 0,
        'url' => '/v1/application_fees/fee_1Sbm4NPFSTNU0nUGQA86GxEr/refunds',
      ),
    ),
    'created_at' => '2026-01-07 19:13:47',
    'updated_at' => '2026-01-07 20:47:44',
  ),
  113 => 
  array (
    'id' => 114,
    'stripe_fee_id' => 'fee_1SbRF1PIzlXORG3aPZ6OEOkm',
    'amount' => '5.41',
    'currency' => 'EUR',
    'created_at_stripe' => '2026-01-07 21:47:44',
    'stripe_account_id' => 'acct_1OYpemPIzlXORG3a',
    'partner_email' => 'feusrl.2019@gmail.com',
    'partner_name' => 'Feu Srl',
    'client_id' => 301,
    'charge_id' => 'py_1SbRF1PIzlXORG3a1ziwkJfB',
    'description' => 'feusrl.2019@gmail.com - acct_1OYpemPIzlXORG3a',
    'period_month' => '2025-12',
    'raw_data' => 
    array (
      'id' => 'fee_1SbRF1PIzlXORG3aPZ6OEOkm',
      'object' => 'application_fee',
      'account' => 'acct_1OYpemPIzlXORG3a',
      'amount' => 541,
      'amount_refunded' => 0,
      'application' => 'ca_Ox2Rdo90ojLoUICobfcdmeRJeF2Icbzs',
      'balance_transaction' => 'txn_1SbRF4Ans9lY52GQ8AMTOx30',
      'charge' => 'py_1SbRF1PIzlXORG3a1ziwkJfB',
      'created' => 1765049643,
      'currency' => 'eur',
      'fee_source' => 
      array (
        'charge' => 'py_1SbRF1PIzlXORG3a1ziwkJfB',
        'type' => 'charge',
      ),
      'livemode' => true,
      'originating_transaction' => 'ch_3SbRC9Ans9lY52GQ0UV02qo1',
      'refunded' => false,
      'refunds' => 
      array (
        'object' => 'list',
        'data' => 
        array (
        ),
        'has_more' => false,
        'total_count' => 0,
        'url' => '/v1/application_fees/fee_1SbRF1PIzlXORG3aPZ6OEOkm/refunds',
      ),
    ),
    'created_at' => '2026-01-07 19:13:47',
    'updated_at' => '2026-01-07 20:47:44',
  ),
  114 => 
  array (
    'id' => 115,
    'stripe_fee_id' => 'fee_1SbQfxPDpTlBWxKeFHF9BHAX',
    'amount' => '3.89',
    'currency' => 'EUR',
    'created_at_stripe' => '2026-01-07 21:47:44',
    'stripe_account_id' => 'acct_1PG1zOPDpTlBWxKe',
    'partner_email' => 'ulocou16@icloud.com',
    'partner_name' => 'Impresa individuale Espinoza Lopez Jhon Erick',
    'client_id' => 312,
    'charge_id' => 'py_1SbQfxPDpTlBWxKexIC0cQpA',
    'description' => 'ulocou16@icloud.com - acct_1PG1zOPDpTlBWxKe',
    'period_month' => '2025-12',
    'raw_data' => 
    array (
      'id' => 'fee_1SbQfxPDpTlBWxKeFHF9BHAX',
      'object' => 'application_fee',
      'account' => 'acct_1PG1zOPDpTlBWxKe',
      'amount' => 389,
      'amount_refunded' => 0,
      'application' => 'ca_Ox2Rdo90ojLoUICobfcdmeRJeF2Icbzs',
      'balance_transaction' => 'txn_1SbQfzAns9lY52GQOyPdbqVw',
      'charge' => 'py_1SbQfxPDpTlBWxKexIC0cQpA',
      'created' => 1765047469,
      'currency' => 'eur',
      'fee_source' => 
      array (
        'charge' => 'py_1SbQfxPDpTlBWxKexIC0cQpA',
        'type' => 'charge',
      ),
      'livemode' => true,
      'originating_transaction' => 'ch_3SbQfRAns9lY52GQ0HD8ZMtH',
      'refunded' => false,
      'refunds' => 
      array (
        'object' => 'list',
        'data' => 
        array (
        ),
        'has_more' => false,
        'total_count' => 0,
        'url' => '/v1/application_fees/fee_1SbQfxPDpTlBWxKeFHF9BHAX/refunds',
      ),
    ),
    'created_at' => '2026-01-07 19:13:48',
    'updated_at' => '2026-01-07 20:47:44',
  ),
  115 => 
  array (
    'id' => 116,
    'stripe_fee_id' => 'fee_1SbQ3BPNB3k6tHL8sigpN9QF',
    'amount' => '4.47',
    'currency' => 'EUR',
    'created_at_stripe' => '2026-01-07 21:47:44',
    'stripe_account_id' => 'acct_1RYoXnPNB3k6tHL8',
    'partner_email' => 'pizzeriaitrecanti@gmail.com',
    'partner_name' => 'Pizzeria I 3 Canti di Bibbiani Mirco & C. SAS',
    'client_id' => 340,
    'charge_id' => 'py_1SbQ3BPNB3k6tHL8kckZS92l',
    'description' => 'pizzeriaitrecanti@gmail.com - acct_1RYoXnPNB3k6tHL8',
    'period_month' => '2025-12',
    'raw_data' => 
    array (
      'id' => 'fee_1SbQ3BPNB3k6tHL8sigpN9QF',
      'object' => 'application_fee',
      'account' => 'acct_1RYoXnPNB3k6tHL8',
      'amount' => 447,
      'amount_refunded' => 0,
      'application' => 'ca_Ox2Rdo90ojLoUICobfcdmeRJeF2Icbzs',
      'balance_transaction' => 'txn_1SbQ3EAns9lY52GQy4UcIEPO',
      'charge' => 'py_1SbQ3BPNB3k6tHL8kckZS92l',
      'created' => 1765045065,
      'currency' => 'eur',
      'fee_source' => 
      array (
        'charge' => 'py_1SbQ3BPNB3k6tHL8kckZS92l',
        'type' => 'charge',
      ),
      'livemode' => true,
      'originating_transaction' => 'ch_3SbPIZAns9lY52GQ1IG5RnoA',
      'refunded' => false,
      'refunds' => 
      array (
        'object' => 'list',
        'data' => 
        array (
        ),
        'has_more' => false,
        'total_count' => 0,
        'url' => '/v1/application_fees/fee_1SbQ3BPNB3k6tHL8sigpN9QF/refunds',
      ),
    ),
    'created_at' => '2026-01-07 19:13:48',
    'updated_at' => '2026-01-07 20:47:44',
  ),
  116 => 
  array (
    'id' => 117,
    'stripe_fee_id' => 'fee_1SbQ2PPIzlXORG3a2HEg2v25',
    'amount' => '5.32',
    'currency' => 'EUR',
    'created_at_stripe' => '2026-01-07 21:47:44',
    'stripe_account_id' => 'acct_1OYpemPIzlXORG3a',
    'partner_email' => 'feusrl.2019@gmail.com',
    'partner_name' => 'Feu Srl',
    'client_id' => 301,
    'charge_id' => 'py_1SbQ2PPIzlXORG3aENDCoyip',
    'description' => 'feusrl.2019@gmail.com - acct_1OYpemPIzlXORG3a',
    'period_month' => '2025-12',
    'raw_data' => 
    array (
      'id' => 'fee_1SbQ2PPIzlXORG3a2HEg2v25',
      'object' => 'application_fee',
      'account' => 'acct_1OYpemPIzlXORG3a',
      'amount' => 532,
      'amount_refunded' => 0,
      'application' => 'ca_Ox2Rdo90ojLoUICobfcdmeRJeF2Icbzs',
      'balance_transaction' => 'txn_1SbQ2RAns9lY52GQYYZBVoo9',
      'charge' => 'py_1SbQ2PPIzlXORG3aENDCoyip',
      'created' => 1765045017,
      'currency' => 'eur',
      'fee_source' => 
      array (
        'charge' => 'py_1SbQ2PPIzlXORG3aENDCoyip',
        'type' => 'charge',
      ),
      'livemode' => true,
      'originating_transaction' => 'ch_3SbPyxAns9lY52GQ17vOY5N6',
      'refunded' => false,
      'refunds' => 
      array (
        'object' => 'list',
        'data' => 
        array (
        ),
        'has_more' => false,
        'total_count' => 0,
        'url' => '/v1/application_fees/fee_1SbQ2PPIzlXORG3a2HEg2v25/refunds',
      ),
    ),
    'created_at' => '2026-01-07 19:13:49',
    'updated_at' => '2026-01-07 20:47:44',
  ),
  117 => 
  array (
    'id' => 118,
    'stripe_fee_id' => 'fee_1SbPNlPAESt8veHw20ub0DNn',
    'amount' => '2.93',
    'currency' => 'EUR',
    'created_at_stripe' => '2026-01-07 21:47:44',
    'stripe_account_id' => 'acct_1ROCdFPAESt8veHw',
    'partner_email' => 'anticatradizione1950@gmail.com',
    'partner_name' => 'Osteria Antica Tradizione srls.',
    'client_id' => 332,
    'charge_id' => 'py_1SbPNlPAESt8veHwxcQKrUAJ',
    'description' => 'anticatradizione1950@gmail.com - acct_1ROCdFPAESt8veHw',
    'period_month' => '2025-12',
    'raw_data' => 
    array (
      'id' => 'fee_1SbPNlPAESt8veHw20ub0DNn',
      'object' => 'application_fee',
      'account' => 'acct_1ROCdFPAESt8veHw',
      'amount' => 293,
      'amount_refunded' => 0,
      'application' => 'ca_Ox2Rdo90ojLoUICobfcdmeRJeF2Icbzs',
      'balance_transaction' => 'txn_1SbPNnAns9lY52GQjqpAB4eS',
      'charge' => 'py_1SbPNlPAESt8veHwxcQKrUAJ',
      'created' => 1765042497,
      'currency' => 'eur',
      'fee_source' => 
      array (
        'charge' => 'py_1SbPNlPAESt8veHwxcQKrUAJ',
        'type' => 'charge',
      ),
      'livemode' => true,
      'originating_transaction' => 'ch_3SbPNAAns9lY52GQ0hQ4U9XB',
      'refunded' => false,
      'refunds' => 
      array (
        'object' => 'list',
        'data' => 
        array (
        ),
        'has_more' => false,
        'total_count' => 0,
        'url' => '/v1/application_fees/fee_1SbPNlPAESt8veHw20ub0DNn/refunds',
      ),
    ),
    'created_at' => '2026-01-07 19:13:50',
    'updated_at' => '2026-01-07 20:47:44',
  ),
  118 => 
  array (
    'id' => 119,
    'stripe_fee_id' => 'fee_1SbOnoPB7qjhlfVa1BED8F1F',
    'amount' => '2.77',
    'currency' => 'EUR',
    'created_at_stripe' => '2026-01-07 21:47:44',
    'stripe_account_id' => 'acct_1OrlPGPB7qjhlfVa',
    'partner_email' => 'laboratoriodellapizza@gmail.com',
    'partner_name' => 'Macrì Domenico',
    'client_id' => 325,
    'charge_id' => 'py_1SbOnoPB7qjhlfVaQNuvhSIS',
    'description' => 'laboratoriodellapizza@gmail.com - acct_1OrlPGPB7qjhlfVa',
    'period_month' => '2025-12',
    'raw_data' => 
    array (
      'id' => 'fee_1SbOnoPB7qjhlfVa1BED8F1F',
      'object' => 'application_fee',
      'account' => 'acct_1OrlPGPB7qjhlfVa',
      'amount' => 277,
      'amount_refunded' => 0,
      'application' => 'ca_Ox2Rdo90ojLoUICobfcdmeRJeF2Icbzs',
      'balance_transaction' => 'txn_1SbOnqAns9lY52GQJ0FVJjnp',
      'charge' => 'py_1SbOnoPB7qjhlfVaQNuvhSIS',
      'created' => 1765040268,
      'currency' => 'eur',
      'fee_source' => 
      array (
        'charge' => 'py_1SbOnoPB7qjhlfVaQNuvhSIS',
        'type' => 'charge',
      ),
      'livemode' => true,
      'originating_transaction' => 'ch_3SbOnIAns9lY52GQ0LDsGvbb',
      'refunded' => false,
      'refunds' => 
      array (
        'object' => 'list',
        'data' => 
        array (
        ),
        'has_more' => false,
        'total_count' => 0,
        'url' => '/v1/application_fees/fee_1SbOnoPB7qjhlfVa1BED8F1F/refunds',
      ),
    ),
    'created_at' => '2026-01-07 19:13:50',
    'updated_at' => '2026-01-07 20:47:44',
  ),
  119 => 
  array (
    'id' => 120,
    'stripe_fee_id' => 'fee_1SbNH6PEpkzElSu4Ni0vbvlN',
    'amount' => '3.56',
    'currency' => 'EUR',
    'created_at_stripe' => '2026-01-07 21:47:44',
    'stripe_account_id' => 'acct_1OrHzGPEpkzElSu4',
    'partner_email' => 'andreadellomodarme@gmail.com',
    'partner_name' => 'PUNTO P DI ANDREA DELL\'OMODARME',
    'client_id' => 383,
    'charge_id' => 'py_1SbNH6PEpkzElSu4JHOEV6ZS',
    'description' => 'andreadellomodarme@gmail.com - acct_1OrHzGPEpkzElSu4',
    'period_month' => '2025-12',
    'raw_data' => 
    array (
      'id' => 'fee_1SbNH6PEpkzElSu4Ni0vbvlN',
      'object' => 'application_fee',
      'account' => 'acct_1OrHzGPEpkzElSu4',
      'amount' => 356,
      'amount_refunded' => 0,
      'application' => 'ca_Ox2Rdo90ojLoUICobfcdmeRJeF2Icbzs',
      'balance_transaction' => 'txn_1SbNH9Ans9lY52GQK1oYGfdF',
      'charge' => 'py_1SbNH6PEpkzElSu4JHOEV6ZS',
      'created' => 1765034396,
      'currency' => 'eur',
      'fee_source' => 
      array (
        'charge' => 'py_1SbNH6PEpkzElSu4JHOEV6ZS',
        'type' => 'charge',
      ),
      'livemode' => true,
      'originating_transaction' => 'ch_3SbNG0Ans9lY52GQ1uZgg3gb',
      'refunded' => false,
      'refunds' => 
      array (
        'object' => 'list',
        'data' => 
        array (
        ),
        'has_more' => false,
        'total_count' => 0,
        'url' => '/v1/application_fees/fee_1SbNH6PEpkzElSu4Ni0vbvlN/refunds',
      ),
    ),
    'created_at' => '2026-01-07 19:13:51',
    'updated_at' => '2026-01-07 20:47:44',
  ),
  120 => 
  array (
    'id' => 121,
    'stripe_fee_id' => 'fee_1SbN64PEpkzElSu40xDGYvbD',
    'amount' => '3.11',
    'currency' => 'EUR',
    'created_at_stripe' => '2026-01-07 21:47:44',
    'stripe_account_id' => 'acct_1OrHzGPEpkzElSu4',
    'partner_email' => 'andreadellomodarme@gmail.com',
    'partner_name' => 'PUNTO P DI ANDREA DELL\'OMODARME',
    'client_id' => 383,
    'charge_id' => 'py_1SbN64PEpkzElSu4bZB9PIqs',
    'description' => 'andreadellomodarme@gmail.com - acct_1OrHzGPEpkzElSu4',
    'period_month' => '2025-12',
    'raw_data' => 
    array (
      'id' => 'fee_1SbN64PEpkzElSu40xDGYvbD',
      'object' => 'application_fee',
      'account' => 'acct_1OrHzGPEpkzElSu4',
      'amount' => 311,
      'amount_refunded' => 0,
      'application' => 'ca_Ox2Rdo90ojLoUICobfcdmeRJeF2Icbzs',
      'balance_transaction' => 'txn_1SbN66Ans9lY52GQecBzGRVZ',
      'charge' => 'py_1SbN64PEpkzElSu4bZB9PIqs',
      'created' => 1765033712,
      'currency' => 'eur',
      'fee_source' => 
      array (
        'charge' => 'py_1SbN64PEpkzElSu4bZB9PIqs',
        'type' => 'charge',
      ),
      'livemode' => true,
      'originating_transaction' => 'ch_3SbN0AAns9lY52GQ0rXBZTe3',
      'refunded' => false,
      'refunds' => 
      array (
        'object' => 'list',
        'data' => 
        array (
        ),
        'has_more' => false,
        'total_count' => 0,
        'url' => '/v1/application_fees/fee_1SbN64PEpkzElSu40xDGYvbD/refunds',
      ),
    ),
    'created_at' => '2026-01-07 19:13:51',
    'updated_at' => '2026-01-07 20:47:44',
  ),
  121 => 
  array (
    'id' => 122,
    'stripe_fee_id' => 'fee_1SbN41PEpkzElSu43tMug98l',
    'amount' => '2.94',
    'currency' => 'EUR',
    'created_at_stripe' => '2026-01-07 21:47:44',
    'stripe_account_id' => 'acct_1OrHzGPEpkzElSu4',
    'partner_email' => 'andreadellomodarme@gmail.com',
    'partner_name' => 'PUNTO P DI ANDREA DELL\'OMODARME',
    'client_id' => 383,
    'charge_id' => 'py_1SbN41PEpkzElSu4S2huR7TJ',
    'description' => 'andreadellomodarme@gmail.com - acct_1OrHzGPEpkzElSu4',
    'period_month' => '2025-12',
    'raw_data' => 
    array (
      'id' => 'fee_1SbN41PEpkzElSu43tMug98l',
      'object' => 'application_fee',
      'account' => 'acct_1OrHzGPEpkzElSu4',
      'amount' => 294,
      'amount_refunded' => 0,
      'application' => 'ca_Ox2Rdo90ojLoUICobfcdmeRJeF2Icbzs',
      'balance_transaction' => 'txn_1SbN43Ans9lY52GQoGjYzi3G',
      'charge' => 'py_1SbN41PEpkzElSu4S2huR7TJ',
      'created' => 1765033585,
      'currency' => 'eur',
      'fee_source' => 
      array (
        'charge' => 'py_1SbN41PEpkzElSu4S2huR7TJ',
        'type' => 'charge',
      ),
      'livemode' => true,
      'originating_transaction' => 'ch_3SbMzcAns9lY52GQ1tz2ANX3',
      'refunded' => false,
      'refunds' => 
      array (
        'object' => 'list',
        'data' => 
        array (
        ),
        'has_more' => false,
        'total_count' => 0,
        'url' => '/v1/application_fees/fee_1SbN41PEpkzElSu43tMug98l/refunds',
      ),
    ),
    'created_at' => '2026-01-07 19:13:52',
    'updated_at' => '2026-01-07 20:47:44',
  ),
  122 => 
  array (
    'id' => 123,
    'stripe_fee_id' => 'fee_1SbMgdPEpkzElSu43b4Vgg31',
    'amount' => '3.17',
    'currency' => 'EUR',
    'created_at_stripe' => '2026-01-07 21:47:44',
    'stripe_account_id' => 'acct_1OrHzGPEpkzElSu4',
    'partner_email' => 'andreadellomodarme@gmail.com',
    'partner_name' => 'PUNTO P DI ANDREA DELL\'OMODARME',
    'client_id' => 383,
    'charge_id' => 'py_1SbMgcPEpkzElSu4SPoRpbAo',
    'description' => 'andreadellomodarme@gmail.com - acct_1OrHzGPEpkzElSu4',
    'period_month' => '2025-12',
    'raw_data' => 
    array (
      'id' => 'fee_1SbMgdPEpkzElSu43b4Vgg31',
      'object' => 'application_fee',
      'account' => 'acct_1OrHzGPEpkzElSu4',
      'amount' => 317,
      'amount_refunded' => 0,
      'application' => 'ca_Ox2Rdo90ojLoUICobfcdmeRJeF2Icbzs',
      'balance_transaction' => 'txn_1SbMgfAns9lY52GQ3scbyifZ',
      'charge' => 'py_1SbMgcPEpkzElSu4SPoRpbAo',
      'created' => 1765032135,
      'currency' => 'eur',
      'fee_source' => 
      array (
        'charge' => 'py_1SbMgcPEpkzElSu4SPoRpbAo',
        'type' => 'charge',
      ),
      'livemode' => true,
      'originating_transaction' => 'ch_3SbMQSAns9lY52GQ0ILtIlOl',
      'refunded' => false,
      'refunds' => 
      array (
        'object' => 'list',
        'data' => 
        array (
        ),
        'has_more' => false,
        'total_count' => 0,
        'url' => '/v1/application_fees/fee_1SbMgdPEpkzElSu43b4Vgg31/refunds',
      ),
    ),
    'created_at' => '2026-01-07 19:13:53',
    'updated_at' => '2026-01-07 20:47:44',
  ),
  123 => 
  array (
    'id' => 124,
    'stripe_fee_id' => 'fee_1SbMeTPEpkzElSu4d6rlCauB',
    'amount' => '3.38',
    'currency' => 'EUR',
    'created_at_stripe' => '2026-01-07 21:47:44',
    'stripe_account_id' => 'acct_1OrHzGPEpkzElSu4',
    'partner_email' => 'andreadellomodarme@gmail.com',
    'partner_name' => 'PUNTO P DI ANDREA DELL\'OMODARME',
    'client_id' => 383,
    'charge_id' => 'py_1SbMeTPEpkzElSu4rZhzM9VX',
    'description' => 'andreadellomodarme@gmail.com - acct_1OrHzGPEpkzElSu4',
    'period_month' => '2025-12',
    'raw_data' => 
    array (
      'id' => 'fee_1SbMeTPEpkzElSu4d6rlCauB',
      'object' => 'application_fee',
      'account' => 'acct_1OrHzGPEpkzElSu4',
      'amount' => 338,
      'amount_refunded' => 0,
      'application' => 'ca_Ox2Rdo90ojLoUICobfcdmeRJeF2Icbzs',
      'balance_transaction' => 'txn_1SbMeWAns9lY52GQl5r4x3ag',
      'charge' => 'py_1SbMeTPEpkzElSu4rZhzM9VX',
      'created' => 1765032001,
      'currency' => 'eur',
      'fee_source' => 
      array (
        'charge' => 'py_1SbMeTPEpkzElSu4rZhzM9VX',
        'type' => 'charge',
      ),
      'livemode' => true,
      'originating_transaction' => 'ch_3SbMKwAns9lY52GQ1s1fEgXm',
      'refunded' => false,
      'refunds' => 
      array (
        'object' => 'list',
        'data' => 
        array (
        ),
        'has_more' => false,
        'total_count' => 0,
        'url' => '/v1/application_fees/fee_1SbMeTPEpkzElSu4d6rlCauB/refunds',
      ),
    ),
    'created_at' => '2026-01-07 19:13:53',
    'updated_at' => '2026-01-07 20:47:44',
  ),
  124 => 
  array (
    'id' => 125,
    'stripe_fee_id' => 'fee_1SbMbvPEpkzElSu4kFrtBtW1',
    'amount' => '3.02',
    'currency' => 'EUR',
    'created_at_stripe' => '2026-01-07 21:47:44',
    'stripe_account_id' => 'acct_1OrHzGPEpkzElSu4',
    'partner_email' => 'andreadellomodarme@gmail.com',
    'partner_name' => 'PUNTO P DI ANDREA DELL\'OMODARME',
    'client_id' => 383,
    'charge_id' => 'py_1SbMbuPEpkzElSu4Cn7YGXmB',
    'description' => 'andreadellomodarme@gmail.com - acct_1OrHzGPEpkzElSu4',
    'period_month' => '2025-12',
    'raw_data' => 
    array (
      'id' => 'fee_1SbMbvPEpkzElSu4kFrtBtW1',
      'object' => 'application_fee',
      'account' => 'acct_1OrHzGPEpkzElSu4',
      'amount' => 302,
      'amount_refunded' => 0,
      'application' => 'ca_Ox2Rdo90ojLoUICobfcdmeRJeF2Icbzs',
      'balance_transaction' => 'txn_1SbMbxAns9lY52GQl1zIbQlt',
      'charge' => 'py_1SbMbuPEpkzElSu4Cn7YGXmB',
      'created' => 1765031843,
      'currency' => 'eur',
      'fee_source' => 
      array (
        'charge' => 'py_1SbMbuPEpkzElSu4Cn7YGXmB',
        'type' => 'charge',
      ),
      'livemode' => true,
      'originating_transaction' => 'ch_3SbMBnAns9lY52GQ05dU4A1G',
      'refunded' => false,
      'refunds' => 
      array (
        'object' => 'list',
        'data' => 
        array (
        ),
        'has_more' => false,
        'total_count' => 0,
        'url' => '/v1/application_fees/fee_1SbMbvPEpkzElSu4kFrtBtW1/refunds',
      ),
    ),
    'created_at' => '2026-01-07 19:13:54',
    'updated_at' => '2026-01-07 20:47:44',
  ),
  125 => 
  array (
    'id' => 126,
    'stripe_fee_id' => 'fee_1SbMR5PEpkzElSu4yx9wQ3Wl',
    'amount' => '4.58',
    'currency' => 'EUR',
    'created_at_stripe' => '2026-01-07 21:47:44',
    'stripe_account_id' => 'acct_1OrHzGPEpkzElSu4',
    'partner_email' => 'andreadellomodarme@gmail.com',
    'partner_name' => 'PUNTO P DI ANDREA DELL\'OMODARME',
    'client_id' => 383,
    'charge_id' => 'py_1SbMR5PEpkzElSu4RIM3PVkd',
    'description' => 'andreadellomodarme@gmail.com - acct_1OrHzGPEpkzElSu4',
    'period_month' => '2025-12',
    'raw_data' => 
    array (
      'id' => 'fee_1SbMR5PEpkzElSu4yx9wQ3Wl',
      'object' => 'application_fee',
      'account' => 'acct_1OrHzGPEpkzElSu4',
      'amount' => 458,
      'amount_refunded' => 0,
      'application' => 'ca_Ox2Rdo90ojLoUICobfcdmeRJeF2Icbzs',
      'balance_transaction' => 'txn_1SbMR8Ans9lY52GQ7isW2yjZ',
      'charge' => 'py_1SbMR5PEpkzElSu4RIM3PVkd',
      'created' => 1765031171,
      'currency' => 'eur',
      'fee_source' => 
      array (
        'charge' => 'py_1SbMR5PEpkzElSu4RIM3PVkd',
        'type' => 'charge',
      ),
      'livemode' => true,
      'originating_transaction' => 'ch_3SbLPqAns9lY52GQ0KhyftEA',
      'refunded' => false,
      'refunds' => 
      array (
        'object' => 'list',
        'data' => 
        array (
        ),
        'has_more' => false,
        'total_count' => 0,
        'url' => '/v1/application_fees/fee_1SbMR5PEpkzElSu4yx9wQ3Wl/refunds',
      ),
    ),
    'created_at' => '2026-01-07 19:13:54',
    'updated_at' => '2026-01-07 20:47:44',
  ),
  126 => 
  array (
    'id' => 127,
    'stripe_fee_id' => 'fee_1SbKSjPCrwFqsIfajBn6DwOf',
    'amount' => '2.83',
    'currency' => 'EUR',
    'created_at_stripe' => '2026-01-07 21:47:44',
    'stripe_account_id' => 'acct_1OhbRBPCrwFqsIfa',
    'partner_email' => 'maeva2000@inwind.it',
    'partner_name' => 'SPEEDY PIZZA DI MASSIMILIANO SILVESTRI',
    'client_id' => 358,
    'charge_id' => 'py_1SbKSjPCrwFqsIfaa6inuGVe',
    'description' => 'maeva2000@inwind.it - acct_1OhbRBPCrwFqsIfa',
    'period_month' => '2025-12',
    'raw_data' => 
    array (
      'id' => 'fee_1SbKSjPCrwFqsIfajBn6DwOf',
      'object' => 'application_fee',
      'account' => 'acct_1OhbRBPCrwFqsIfa',
      'amount' => 283,
      'amount_refunded' => 0,
      'application' => 'ca_Ox2Rdo90ojLoUICobfcdmeRJeF2Icbzs',
      'balance_transaction' => 'txn_1SbKSnAns9lY52GQzDogaEOs',
      'charge' => 'py_1SbKSjPCrwFqsIfaa6inuGVe',
      'created' => 1765023585,
      'currency' => 'eur',
      'fee_source' => 
      array (
        'charge' => 'py_1SbKSjPCrwFqsIfaa6inuGVe',
        'type' => 'charge',
      ),
      'livemode' => true,
      'originating_transaction' => 'ch_3SbHiEAns9lY52GQ1ibiTIoi',
      'refunded' => false,
      'refunds' => 
      array (
        'object' => 'list',
        'data' => 
        array (
        ),
        'has_more' => false,
        'total_count' => 0,
        'url' => '/v1/application_fees/fee_1SbKSjPCrwFqsIfajBn6DwOf/refunds',
      ),
    ),
    'created_at' => '2026-01-07 19:13:55',
    'updated_at' => '2026-01-07 20:47:44',
  ),
  127 => 
  array (
    'id' => 128,
    'stripe_fee_id' => 'fee_1Sb1oTPEpkzElSu4njC9IVzL',
    'amount' => '3.08',
    'currency' => 'EUR',
    'created_at_stripe' => '2026-01-07 21:47:44',
    'stripe_account_id' => 'acct_1OrHzGPEpkzElSu4',
    'partner_email' => 'andreadellomodarme@gmail.com',
    'partner_name' => 'PUNTO P DI ANDREA DELL\'OMODARME',
    'client_id' => 383,
    'charge_id' => 'py_1Sb1oTPEpkzElSu46IypDIK5',
    'description' => 'andreadellomodarme@gmail.com - acct_1OrHzGPEpkzElSu4',
    'period_month' => '2025-12',
    'raw_data' => 
    array (
      'id' => 'fee_1Sb1oTPEpkzElSu4njC9IVzL',
      'object' => 'application_fee',
      'account' => 'acct_1OrHzGPEpkzElSu4',
      'amount' => 308,
      'amount_refunded' => 0,
      'application' => 'ca_Ox2Rdo90ojLoUICobfcdmeRJeF2Icbzs',
      'balance_transaction' => 'txn_1Sb1oVAns9lY52GQGBZvFd2d',
      'charge' => 'py_1Sb1oTPEpkzElSu46IypDIK5',
      'created' => 1764951897,
      'currency' => 'eur',
      'fee_source' => 
      array (
        'charge' => 'py_1Sb1oTPEpkzElSu46IypDIK5',
        'type' => 'charge',
      ),
      'livemode' => true,
      'originating_transaction' => 'ch_3Sb1h1Ans9lY52GQ0nnqTmnJ',
      'refunded' => false,
      'refunds' => 
      array (
        'object' => 'list',
        'data' => 
        array (
        ),
        'has_more' => false,
        'total_count' => 0,
        'url' => '/v1/application_fees/fee_1Sb1oTPEpkzElSu4njC9IVzL/refunds',
      ),
    ),
    'created_at' => '2026-01-07 19:13:56',
    'updated_at' => '2026-01-07 20:47:44',
  ),
  128 => 
  array (
    'id' => 129,
    'stripe_fee_id' => 'fee_1SahxXPDpTlBWxKecdF0gxp2',
    'amount' => '4.79',
    'currency' => 'EUR',
    'created_at_stripe' => '2026-01-07 21:47:44',
    'stripe_account_id' => 'acct_1PG1zOPDpTlBWxKe',
    'partner_email' => 'ulocou16@icloud.com',
    'partner_name' => 'Impresa individuale Espinoza Lopez Jhon Erick',
    'client_id' => 312,
    'charge_id' => 'py_1SahxXPDpTlBWxKePO7o5EQZ',
    'description' => 'ulocou16@icloud.com - acct_1PG1zOPDpTlBWxKe',
    'period_month' => '2025-12',
    'raw_data' => 
    array (
      'id' => 'fee_1SahxXPDpTlBWxKecdF0gxp2',
      'object' => 'application_fee',
      'account' => 'acct_1PG1zOPDpTlBWxKe',
      'amount' => 479,
      'amount_refunded' => 0,
      'application' => 'ca_Ox2Rdo90ojLoUICobfcdmeRJeF2Icbzs',
      'balance_transaction' => 'txn_1SahxZAns9lY52GQr8KzaTWm',
      'charge' => 'py_1SahxXPDpTlBWxKePO7o5EQZ',
      'created' => 1764875579,
      'currency' => 'eur',
      'fee_source' => 
      array (
        'charge' => 'py_1SahxXPDpTlBWxKePO7o5EQZ',
        'type' => 'charge',
      ),
      'livemode' => true,
      'originating_transaction' => 'ch_3SahqqAns9lY52GQ1QJusZmw',
      'refunded' => false,
      'refunds' => 
      array (
        'object' => 'list',
        'data' => 
        array (
        ),
        'has_more' => false,
        'total_count' => 0,
        'url' => '/v1/application_fees/fee_1SahxXPDpTlBWxKecdF0gxp2/refunds',
      ),
    ),
    'created_at' => '2026-01-07 19:13:56',
    'updated_at' => '2026-01-07 20:47:44',
  ),
  129 => 
  array (
    'id' => 130,
    'stripe_fee_id' => 'fee_1SafCUPEpkzElSu45A5IpV6A',
    'amount' => '2.98',
    'currency' => 'EUR',
    'created_at_stripe' => '2026-01-07 21:47:44',
    'stripe_account_id' => 'acct_1OrHzGPEpkzElSu4',
    'partner_email' => 'andreadellomodarme@gmail.com',
    'partner_name' => 'PUNTO P DI ANDREA DELL\'OMODARME',
    'client_id' => 383,
    'charge_id' => 'py_1SafCUPEpkzElSu45rBE9yDo',
    'description' => 'andreadellomodarme@gmail.com - acct_1OrHzGPEpkzElSu4',
    'period_month' => '2025-12',
    'raw_data' => 
    array (
      'id' => 'fee_1SafCUPEpkzElSu45A5IpV6A',
      'object' => 'application_fee',
      'account' => 'acct_1OrHzGPEpkzElSu4',
      'amount' => 298,
      'amount_refunded' => 0,
      'application' => 'ca_Ox2Rdo90ojLoUICobfcdmeRJeF2Icbzs',
      'balance_transaction' => 'txn_1SafCXAns9lY52GQ4mwaY1nE',
      'charge' => 'py_1SafCUPEpkzElSu45rBE9yDo',
      'created' => 1764864974,
      'currency' => 'eur',
      'fee_source' => 
      array (
        'charge' => 'py_1SafCUPEpkzElSu45rBE9yDo',
        'type' => 'charge',
      ),
      'livemode' => true,
      'originating_transaction' => 'ch_3SaeeiAns9lY52GQ0s2bDbUL',
      'refunded' => false,
      'refunds' => 
      array (
        'object' => 'list',
        'data' => 
        array (
        ),
        'has_more' => false,
        'total_count' => 0,
        'url' => '/v1/application_fees/fee_1SafCUPEpkzElSu45A5IpV6A/refunds',
      ),
    ),
    'created_at' => '2026-01-07 19:13:57',
    'updated_at' => '2026-01-07 20:47:44',
  ),
  130 => 
  array (
    'id' => 131,
    'stripe_fee_id' => 'fee_1SaaSmPGF6joqoUStnUmQcr3',
    'amount' => '3.78',
    'currency' => 'EUR',
    'created_at_stripe' => '2026-01-07 21:47:44',
    'stripe_account_id' => 'acct_1PGKQRPGF6joqoUS',
    'partner_email' => 'scompostodue@yahoo.com',
    'partner_name' => 'Scompostodue sas',
    'client_id' => 353,
    'charge_id' => 'py_1SaaSlPGF6joqoUSvjxKD5g9',
    'description' => 'scompostodue@yahoo.com - acct_1PGKQRPGF6joqoUS',
    'period_month' => '2025-12',
    'raw_data' => 
    array (
      'id' => 'fee_1SaaSmPGF6joqoUStnUmQcr3',
      'object' => 'application_fee',
      'account' => 'acct_1PGKQRPGF6joqoUS',
      'amount' => 378,
      'amount_refunded' => 0,
      'application' => 'ca_Ox2Rdo90ojLoUICobfcdmeRJeF2Icbzs',
      'balance_transaction' => 'txn_1SaaSoAns9lY52GQAJUVRsgo',
      'charge' => 'py_1SaaSlPGF6joqoUSvjxKD5g9',
      'created' => 1764846764,
      'currency' => 'eur',
      'fee_source' => 
      array (
        'charge' => 'py_1SaaSlPGF6joqoUSvjxKD5g9',
        'type' => 'charge',
      ),
      'livemode' => true,
      'originating_transaction' => 'ch_3SaX9wAns9lY52GQ00rf8w7z',
      'refunded' => false,
      'refunds' => 
      array (
        'object' => 'list',
        'data' => 
        array (
        ),
        'has_more' => false,
        'total_count' => 0,
        'url' => '/v1/application_fees/fee_1SaaSmPGF6joqoUStnUmQcr3/refunds',
      ),
    ),
    'created_at' => '2026-01-07 19:13:58',
    'updated_at' => '2026-01-07 20:47:44',
  ),
  131 => 
  array (
    'id' => 132,
    'stripe_fee_id' => 'fee_1SaDX1PCrwFqsIfarlJ9UISe',
    'amount' => '2.77',
    'currency' => 'EUR',
    'created_at_stripe' => '2026-01-07 21:47:44',
    'stripe_account_id' => 'acct_1OhbRBPCrwFqsIfa',
    'partner_email' => 'maeva2000@inwind.it',
    'partner_name' => 'SPEEDY PIZZA DI MASSIMILIANO SILVESTRI',
    'client_id' => 358,
    'charge_id' => 'py_1SaDX1PCrwFqsIfaOn2GLAEM',
    'description' => 'maeva2000@inwind.it - acct_1OhbRBPCrwFqsIfa',
    'period_month' => '2025-12',
    'raw_data' => 
    array (
      'id' => 'fee_1SaDX1PCrwFqsIfarlJ9UISe',
      'object' => 'application_fee',
      'account' => 'acct_1OhbRBPCrwFqsIfa',
      'amount' => 277,
      'amount_refunded' => 0,
      'application' => 'ca_Ox2Rdo90ojLoUICobfcdmeRJeF2Icbzs',
      'balance_transaction' => 'txn_1SaDX3Ans9lY52GQL31g4jAU',
      'charge' => 'py_1SaDX1PCrwFqsIfaOn2GLAEM',
      'created' => 1764758615,
      'currency' => 'eur',
      'fee_source' => 
      array (
        'charge' => 'py_1SaDX1PCrwFqsIfaOn2GLAEM',
        'type' => 'charge',
      ),
      'livemode' => true,
      'originating_transaction' => 'ch_3SaDU8Ans9lY52GQ1s7iDwXd',
      'refunded' => false,
      'refunds' => 
      array (
        'object' => 'list',
        'data' => 
        array (
        ),
        'has_more' => false,
        'total_count' => 0,
        'url' => '/v1/application_fees/fee_1SaDX1PCrwFqsIfarlJ9UISe/refunds',
      ),
    ),
    'created_at' => '2026-01-07 19:13:58',
    'updated_at' => '2026-01-07 20:47:44',
  ),
  132 => 
  array (
    'id' => 133,
    'stripe_fee_id' => 'fee_1SZwcWPEpkzElSu4vaxocTkJ',
    'amount' => '3.16',
    'currency' => 'EUR',
    'created_at_stripe' => '2026-01-07 21:47:44',
    'stripe_account_id' => 'acct_1OrHzGPEpkzElSu4',
    'partner_email' => 'andreadellomodarme@gmail.com',
    'partner_name' => 'PUNTO P DI ANDREA DELL\'OMODARME',
    'client_id' => 383,
    'charge_id' => 'py_1SZwcWPEpkzElSu4bGtPwdGb',
    'description' => 'andreadellomodarme@gmail.com - acct_1OrHzGPEpkzElSu4',
    'period_month' => '2025-12',
    'raw_data' => 
    array (
      'id' => 'fee_1SZwcWPEpkzElSu4vaxocTkJ',
      'object' => 'application_fee',
      'account' => 'acct_1OrHzGPEpkzElSu4',
      'amount' => 316,
      'amount_refunded' => 0,
      'application' => 'ca_Ox2Rdo90ojLoUICobfcdmeRJeF2Icbzs',
      'balance_transaction' => 'txn_1SZwcYAns9lY52GQ9aDaj63i',
      'charge' => 'py_1SZwcWPEpkzElSu4bGtPwdGb',
      'created' => 1764693608,
      'currency' => 'eur',
      'fee_source' => 
      array (
        'charge' => 'py_1SZwcWPEpkzElSu4bGtPwdGb',
        'type' => 'charge',
      ),
      'livemode' => true,
      'originating_transaction' => 'ch_3SZwRTAns9lY52GQ11cLlrS5',
      'refunded' => false,
      'refunds' => 
      array (
        'object' => 'list',
        'data' => 
        array (
        ),
        'has_more' => false,
        'total_count' => 0,
        'url' => '/v1/application_fees/fee_1SZwcWPEpkzElSu4vaxocTkJ/refunds',
      ),
    ),
    'created_at' => '2026-01-07 19:13:59',
    'updated_at' => '2026-01-07 20:47:44',
  ),
  133 => 
  array (
    'id' => 134,
    'stripe_fee_id' => 'fee_1SZapBPEpkzElSu4FeXDdBGv',
    'amount' => '3.05',
    'currency' => 'EUR',
    'created_at_stripe' => '2026-01-07 21:47:44',
    'stripe_account_id' => 'acct_1OrHzGPEpkzElSu4',
    'partner_email' => 'andreadellomodarme@gmail.com',
    'partner_name' => 'PUNTO P DI ANDREA DELL\'OMODARME',
    'client_id' => 383,
    'charge_id' => 'py_1SZapAPEpkzElSu4zB3iEgIS',
    'description' => 'andreadellomodarme@gmail.com - acct_1OrHzGPEpkzElSu4',
    'period_month' => '2025-12',
    'raw_data' => 
    array (
      'id' => 'fee_1SZapBPEpkzElSu4FeXDdBGv',
      'object' => 'application_fee',
      'account' => 'acct_1OrHzGPEpkzElSu4',
      'amount' => 305,
      'amount_refunded' => 0,
      'application' => 'ca_Ox2Rdo90ojLoUICobfcdmeRJeF2Icbzs',
      'balance_transaction' => 'txn_1SZapDAns9lY52GQgQ0mHSdJ',
      'charge' => 'py_1SZapAPEpkzElSu4zB3iEgIS',
      'created' => 1764609825,
      'currency' => 'eur',
      'fee_source' => 
      array (
        'charge' => 'py_1SZapAPEpkzElSu4zB3iEgIS',
        'type' => 'charge',
      ),
      'livemode' => true,
      'originating_transaction' => 'ch_3SZaggAns9lY52GQ1RInZOrh',
      'refunded' => false,
      'refunds' => 
      array (
        'object' => 'list',
        'data' => 
        array (
        ),
        'has_more' => false,
        'total_count' => 0,
        'url' => '/v1/application_fees/fee_1SZapBPEpkzElSu4FeXDdBGv/refunds',
      ),
    ),
    'created_at' => '2026-01-07 19:13:59',
    'updated_at' => '2026-01-07 20:47:44',
  ),
  134 => 
  array (
    'id' => 135,
    'stripe_fee_id' => 'fee_1SZGbZACqrpp0TG5gF16jnDW',
    'amount' => '4.26',
    'currency' => 'EUR',
    'created_at_stripe' => '2026-01-07 21:47:44',
    'stripe_account_id' => 'acct_1RyUiVACqrpp0TG5',
    'partner_email' => 'pizzeriaitrecanti@gmail.com',
    'partner_name' => 'Pizzeria I 3 Canti di Bibbiani Mirco & C. SAS',
    'client_id' => 340,
    'charge_id' => 'py_1SZGbYACqrpp0TG5T4w5KHfr',
    'description' => 'pizzeriaitrecanti@gmail.com - acct_1RyUiVACqrpp0TG5',
    'period_month' => '2025-11',
    'raw_data' => 
    array (
      'id' => 'fee_1SZGbZACqrpp0TG5gF16jnDW',
      'object' => 'application_fee',
      'account' => 'acct_1RyUiVACqrpp0TG5',
      'amount' => 426,
      'amount_refunded' => 0,
      'application' => 'ca_Ox2Rdo90ojLoUICobfcdmeRJeF2Icbzs',
      'balance_transaction' => 'txn_1SZGbbAns9lY52GQAjIGqSNQ',
      'charge' => 'py_1SZGbYACqrpp0TG5T4w5KHfr',
      'created' => 1764532101,
      'currency' => 'eur',
      'fee_source' => 
      array (
        'charge' => 'py_1SZGbYACqrpp0TG5T4w5KHfr',
        'type' => 'charge',
      ),
      'livemode' => true,
      'originating_transaction' => 'ch_3SZEVUAns9lY52GQ0Z4GriuU',
      'refunded' => false,
      'refunds' => 
      array (
        'object' => 'list',
        'data' => 
        array (
        ),
        'has_more' => false,
        'total_count' => 0,
        'url' => '/v1/application_fees/fee_1SZGbZACqrpp0TG5gF16jnDW/refunds',
      ),
    ),
    'created_at' => '2026-01-07 19:14:00',
    'updated_at' => '2026-01-07 20:47:44',
  ),
  135 => 
  array (
    'id' => 136,
    'stripe_fee_id' => 'fee_1SZG5FAcaExTZKe8ld9u49WS',
    'amount' => '3.93',
    'currency' => 'EUR',
    'created_at_stripe' => '2026-01-07 21:47:44',
    'stripe_account_id' => 'acct_1QsMHWAcaExTZKe8',
    'partner_email' => 'nclcucc@gmail.com',
    'partner_name' => 'La Loggia sul Mare SRL',
    'client_id' => 322,
    'charge_id' => 'py_1SZG5FAcaExTZKe8k4bed8g3',
    'description' => 'nclcucc@gmail.com - acct_1QsMHWAcaExTZKe8',
    'period_month' => '2025-11',
    'raw_data' => 
    array (
      'id' => 'fee_1SZG5FAcaExTZKe8ld9u49WS',
      'object' => 'application_fee',
      'account' => 'acct_1QsMHWAcaExTZKe8',
      'amount' => 393,
      'amount_refunded' => 0,
      'application' => 'ca_Ox2Rdo90ojLoUICobfcdmeRJeF2Icbzs',
      'balance_transaction' => 'txn_1SZG5JAns9lY52GQ0Y3BNWsw',
      'charge' => 'py_1SZG5FAcaExTZKe8k4bed8g3',
      'created' => 1764530097,
      'currency' => 'eur',
      'fee_source' => 
      array (
        'charge' => 'py_1SZG5FAcaExTZKe8k4bed8g3',
        'type' => 'charge',
      ),
      'livemode' => true,
      'originating_transaction' => 'ch_3SZFmpAns9lY52GQ0fT5ssw4',
      'refunded' => false,
      'refunds' => 
      array (
        'object' => 'list',
        'data' => 
        array (
        ),
        'has_more' => false,
        'total_count' => 0,
        'url' => '/v1/application_fees/fee_1SZG5FAcaExTZKe8ld9u49WS/refunds',
      ),
    ),
    'created_at' => '2026-01-07 19:14:01',
    'updated_at' => '2026-01-07 20:47:44',
  ),
  136 => 
  array (
    'id' => 137,
    'stripe_fee_id' => 'fee_1SZEe0PIzlXORG3a9JDmm1zt',
    'amount' => '4.72',
    'currency' => 'EUR',
    'created_at_stripe' => '2026-01-07 21:47:44',
    'stripe_account_id' => 'acct_1OYpemPIzlXORG3a',
    'partner_email' => 'feusrl.2019@gmail.com',
    'partner_name' => 'Feu Srl',
    'client_id' => 301,
    'charge_id' => 'py_1SZEe0PIzlXORG3aakI0Ivqv',
    'description' => 'feusrl.2019@gmail.com - acct_1OYpemPIzlXORG3a',
    'period_month' => '2025-11',
    'raw_data' => 
    array (
      'id' => 'fee_1SZEe0PIzlXORG3a9JDmm1zt',
      'object' => 'application_fee',
      'account' => 'acct_1OYpemPIzlXORG3a',
      'amount' => 472,
      'amount_refunded' => 0,
      'application' => 'ca_Ox2Rdo90ojLoUICobfcdmeRJeF2Icbzs',
      'balance_transaction' => 'txn_1SZEe2Ans9lY52GQ5Klb27fQ',
      'charge' => 'py_1SZEe0PIzlXORG3aakI0Ivqv',
      'created' => 1764524564,
      'currency' => 'eur',
      'fee_source' => 
      array (
        'charge' => 'py_1SZEe0PIzlXORG3aakI0Ivqv',
        'type' => 'charge',
      ),
      'livemode' => true,
      'originating_transaction' => 'ch_3SZE9kAns9lY52GQ1tYhXYY6',
      'refunded' => false,
      'refunds' => 
      array (
        'object' => 'list',
        'data' => 
        array (
        ),
        'has_more' => false,
        'total_count' => 0,
        'url' => '/v1/application_fees/fee_1SZEe0PIzlXORG3a9JDmm1zt/refunds',
      ),
    ),
    'created_at' => '2026-01-07 19:14:01',
    'updated_at' => '2026-01-07 20:47:44',
  ),
  137 => 
  array (
    'id' => 138,
    'stripe_fee_id' => 'fee_1SZEWbPB7qjhlfVaolaFDSh1',
    'amount' => '3.72',
    'currency' => 'EUR',
    'created_at_stripe' => '2026-01-07 21:47:44',
    'stripe_account_id' => 'acct_1OrlPGPB7qjhlfVa',
    'partner_email' => 'laboratoriodellapizza@gmail.com',
    'partner_name' => 'Macrì Domenico',
    'client_id' => 325,
    'charge_id' => 'py_1SZEWaPB7qjhlfVavHNV8Mq9',
    'description' => 'laboratoriodellapizza@gmail.com - acct_1OrlPGPB7qjhlfVa',
    'period_month' => '2025-11',
    'raw_data' => 
    array (
      'id' => 'fee_1SZEWbPB7qjhlfVaolaFDSh1',
      'object' => 'application_fee',
      'account' => 'acct_1OrlPGPB7qjhlfVa',
      'amount' => 372,
      'amount_refunded' => 0,
      'application' => 'ca_Ox2Rdo90ojLoUICobfcdmeRJeF2Icbzs',
      'balance_transaction' => 'txn_1SZEWdAns9lY52GQVilENNn8',
      'charge' => 'py_1SZEWaPB7qjhlfVavHNV8Mq9',
      'created' => 1764524105,
      'currency' => 'eur',
      'fee_source' => 
      array (
        'charge' => 'py_1SZEWaPB7qjhlfVavHNV8Mq9',
        'type' => 'charge',
      ),
      'livemode' => true,
      'originating_transaction' => 'ch_3SZEVoAns9lY52GQ18OrBDom',
      'refunded' => false,
      'refunds' => 
      array (
        'object' => 'list',
        'data' => 
        array (
        ),
        'has_more' => false,
        'total_count' => 0,
        'url' => '/v1/application_fees/fee_1SZEWbPB7qjhlfVaolaFDSh1/refunds',
      ),
    ),
    'created_at' => '2026-01-07 19:14:02',
    'updated_at' => '2026-01-07 20:47:44',
  ),
  138 => 
  array (
    'id' => 139,
    'stripe_fee_id' => 'fee_1SZEN9PB7qjhlfVaLe42av8I',
    'amount' => '2.95',
    'currency' => 'EUR',
    'created_at_stripe' => '2026-01-07 21:47:44',
    'stripe_account_id' => 'acct_1OrlPGPB7qjhlfVa',
    'partner_email' => 'laboratoriodellapizza@gmail.com',
    'partner_name' => 'Macrì Domenico',
    'client_id' => 325,
    'charge_id' => 'py_1SZEN9PB7qjhlfVaTuEnY2SO',
    'description' => 'laboratoriodellapizza@gmail.com - acct_1OrlPGPB7qjhlfVa',
    'period_month' => '2025-11',
    'raw_data' => 
    array (
      'id' => 'fee_1SZEN9PB7qjhlfVaLe42av8I',
      'object' => 'application_fee',
      'account' => 'acct_1OrlPGPB7qjhlfVa',
      'amount' => 295,
      'amount_refunded' => 0,
      'application' => 'ca_Ox2Rdo90ojLoUICobfcdmeRJeF2Icbzs',
      'balance_transaction' => 'txn_1SZENBAns9lY52GQ6XAzr2oV',
      'charge' => 'py_1SZEN9PB7qjhlfVaTuEnY2SO',
      'created' => 1764523519,
      'currency' => 'eur',
      'fee_source' => 
      array (
        'charge' => 'py_1SZEN9PB7qjhlfVaTuEnY2SO',
        'type' => 'charge',
      ),
      'livemode' => true,
      'originating_transaction' => 'ch_3SZEMVAns9lY52GQ0fSX1j9o',
      'refunded' => false,
      'refunds' => 
      array (
        'object' => 'list',
        'data' => 
        array (
        ),
        'has_more' => false,
        'total_count' => 0,
        'url' => '/v1/application_fees/fee_1SZEN9PB7qjhlfVaLe42av8I/refunds',
      ),
    ),
    'created_at' => '2026-01-07 19:14:02',
    'updated_at' => '2026-01-07 20:47:44',
  ),
  139 => 
  array (
    'id' => 140,
    'stripe_fee_id' => 'fee_1SZDPyPEpkzElSu4LROHKBJf',
    'amount' => '2.93',
    'currency' => 'EUR',
    'created_at_stripe' => '2026-01-07 21:47:44',
    'stripe_account_id' => 'acct_1OrHzGPEpkzElSu4',
    'partner_email' => 'andreadellomodarme@gmail.com',
    'partner_name' => 'PUNTO P DI ANDREA DELL\'OMODARME',
    'client_id' => 383,
    'charge_id' => 'py_1SZDPyPEpkzElSu4hkidOc6B',
    'description' => 'andreadellomodarme@gmail.com - acct_1OrHzGPEpkzElSu4',
    'period_month' => '2025-11',
    'raw_data' => 
    array (
      'id' => 'fee_1SZDPyPEpkzElSu4LROHKBJf',
      'object' => 'application_fee',
      'account' => 'acct_1OrHzGPEpkzElSu4',
      'amount' => 293,
      'amount_refunded' => 0,
      'application' => 'ca_Ox2Rdo90ojLoUICobfcdmeRJeF2Icbzs',
      'balance_transaction' => 'txn_1SZDQ1Ans9lY52GQ2Ikod78w',
      'charge' => 'py_1SZDPyPEpkzElSu4hkidOc6B',
      'created' => 1764519850,
      'currency' => 'eur',
      'fee_source' => 
      array (
        'charge' => 'py_1SZDPyPEpkzElSu4hkidOc6B',
        'type' => 'charge',
      ),
      'livemode' => true,
      'originating_transaction' => 'ch_3SZD9BAns9lY52GQ1P99ifPa',
      'refunded' => false,
      'refunds' => 
      array (
        'object' => 'list',
        'data' => 
        array (
        ),
        'has_more' => false,
        'total_count' => 0,
        'url' => '/v1/application_fees/fee_1SZDPyPEpkzElSu4LROHKBJf/refunds',
      ),
    ),
    'created_at' => '2026-01-07 19:14:03',
    'updated_at' => '2026-01-07 20:47:44',
  ),
  140 => 
  array (
    'id' => 141,
    'stripe_fee_id' => 'fee_1SZD4fPB7qjhlfVaPZYIvxUD',
    'amount' => '2.93',
    'currency' => 'EUR',
    'created_at_stripe' => '2026-01-07 21:47:44',
    'stripe_account_id' => 'acct_1OrlPGPB7qjhlfVa',
    'partner_email' => 'laboratoriodellapizza@gmail.com',
    'partner_name' => 'Macrì Domenico',
    'client_id' => 325,
    'charge_id' => 'py_1SZD4ePB7qjhlfVaSQ5VMPeZ',
    'description' => 'laboratoriodellapizza@gmail.com - acct_1OrlPGPB7qjhlfVa',
    'period_month' => '2025-11',
    'raw_data' => 
    array (
      'id' => 'fee_1SZD4fPB7qjhlfVaPZYIvxUD',
      'object' => 'application_fee',
      'account' => 'acct_1OrlPGPB7qjhlfVa',
      'amount' => 293,
      'amount_refunded' => 0,
      'application' => 'ca_Ox2Rdo90ojLoUICobfcdmeRJeF2Icbzs',
      'balance_transaction' => 'txn_1SZD4hAns9lY52GQla4LTHAG',
      'charge' => 'py_1SZD4ePB7qjhlfVaSQ5VMPeZ',
      'created' => 1764518529,
      'currency' => 'eur',
      'fee_source' => 
      array (
        'charge' => 'py_1SZD4ePB7qjhlfVaSQ5VMPeZ',
        'type' => 'charge',
      ),
      'livemode' => true,
      'originating_transaction' => 'ch_3SZCNrAns9lY52GQ0tUsYMVK',
      'refunded' => false,
      'refunds' => 
      array (
        'object' => 'list',
        'data' => 
        array (
        ),
        'has_more' => false,
        'total_count' => 0,
        'url' => '/v1/application_fees/fee_1SZD4fPB7qjhlfVaPZYIvxUD/refunds',
      ),
    ),
    'created_at' => '2026-01-07 19:14:03',
    'updated_at' => '2026-01-07 20:47:44',
  ),
  141 => 
  array (
    'id' => 142,
    'stripe_fee_id' => 'fee_1SYt71PIzlXORG3ao3fDmpqc',
    'amount' => '4.94',
    'currency' => 'EUR',
    'created_at_stripe' => '2026-01-07 21:47:44',
    'stripe_account_id' => 'acct_1OYpemPIzlXORG3a',
    'partner_email' => 'feusrl.2019@gmail.com',
    'partner_name' => 'Feu Srl',
    'client_id' => 301,
    'charge_id' => 'py_1SYt71PIzlXORG3awWjO4crh',
    'description' => 'feusrl.2019@gmail.com - acct_1OYpemPIzlXORG3a',
    'period_month' => '2025-11',
    'raw_data' => 
    array (
      'id' => 'fee_1SYt71PIzlXORG3ao3fDmpqc',
      'object' => 'application_fee',
      'account' => 'acct_1OYpemPIzlXORG3a',
      'amount' => 494,
      'amount_refunded' => 0,
      'application' => 'ca_Ox2Rdo90ojLoUICobfcdmeRJeF2Icbzs',
      'balance_transaction' => 'txn_1SYt74Ans9lY52GQubSmubEr',
      'charge' => 'py_1SYt71PIzlXORG3awWjO4crh',
      'created' => 1764441795,
      'currency' => 'eur',
      'fee_source' => 
      array (
        'charge' => 'py_1SYt71PIzlXORG3awWjO4crh',
        'type' => 'charge',
      ),
      'livemode' => true,
      'originating_transaction' => 'ch_3SYsydAns9lY52GQ10Q3m9yD',
      'refunded' => false,
      'refunds' => 
      array (
        'object' => 'list',
        'data' => 
        array (
        ),
        'has_more' => false,
        'total_count' => 0,
        'url' => '/v1/application_fees/fee_1SYt71PIzlXORG3ao3fDmpqc/refunds',
      ),
    ),
    'created_at' => '2026-01-07 19:14:04',
    'updated_at' => '2026-01-07 20:47:44',
  ),
  142 => 
  array (
    'id' => 143,
    'stripe_fee_id' => 'fee_1SYswsPIzlXORG3a69w7IQeu',
    'amount' => '4.99',
    'currency' => 'EUR',
    'created_at_stripe' => '2026-01-07 21:47:44',
    'stripe_account_id' => 'acct_1OYpemPIzlXORG3a',
    'partner_email' => 'feusrl.2019@gmail.com',
    'partner_name' => 'Feu Srl',
    'client_id' => 301,
    'charge_id' => 'py_1SYswrPIzlXORG3agLUebtDc',
    'description' => 'feusrl.2019@gmail.com - acct_1OYpemPIzlXORG3a',
    'period_month' => '2025-11',
    'raw_data' => 
    array (
      'id' => 'fee_1SYswsPIzlXORG3a69w7IQeu',
      'object' => 'application_fee',
      'account' => 'acct_1OYpemPIzlXORG3a',
      'amount' => 499,
      'amount_refunded' => 0,
      'application' => 'ca_Ox2Rdo90ojLoUICobfcdmeRJeF2Icbzs',
      'balance_transaction' => 'txn_1SYswuAns9lY52GQuL07kWYf',
      'charge' => 'py_1SYswrPIzlXORG3agLUebtDc',
      'created' => 1764441166,
      'currency' => 'eur',
      'fee_source' => 
      array (
        'charge' => 'py_1SYswrPIzlXORG3agLUebtDc',
        'type' => 'charge',
      ),
      'livemode' => true,
      'originating_transaction' => 'ch_3SYstPAns9lY52GQ0zEAVB4j',
      'refunded' => false,
      'refunds' => 
      array (
        'object' => 'list',
        'data' => 
        array (
        ),
        'has_more' => false,
        'total_count' => 0,
        'url' => '/v1/application_fees/fee_1SYswsPIzlXORG3a69w7IQeu/refunds',
      ),
    ),
    'created_at' => '2026-01-07 19:14:05',
    'updated_at' => '2026-01-07 20:47:44',
  ),
  143 => 
  array (
    'id' => 144,
    'stripe_fee_id' => 'fee_1SYsKdPIzlXORG3adD0LStdW',
    'amount' => '3.50',
    'currency' => 'EUR',
    'created_at_stripe' => '2026-01-07 21:47:44',
    'stripe_account_id' => 'acct_1OYpemPIzlXORG3a',
    'partner_email' => 'feusrl.2019@gmail.com',
    'partner_name' => 'Feu Srl',
    'client_id' => 301,
    'charge_id' => 'py_1SYsKcPIzlXORG3amfl3Mvlf',
    'description' => 'feusrl.2019@gmail.com - acct_1OYpemPIzlXORG3a',
    'period_month' => '2025-11',
    'raw_data' => 
    array (
      'id' => 'fee_1SYsKdPIzlXORG3adD0LStdW',
      'object' => 'application_fee',
      'account' => 'acct_1OYpemPIzlXORG3a',
      'amount' => 350,
      'amount_refunded' => 0,
      'application' => 'ca_Ox2Rdo90ojLoUICobfcdmeRJeF2Icbzs',
      'balance_transaction' => 'txn_1SYsKfAns9lY52GQzXDyMSOC',
      'charge' => 'py_1SYsKcPIzlXORG3amfl3Mvlf',
      'created' => 1764438795,
      'currency' => 'eur',
      'fee_source' => 
      array (
        'charge' => 'py_1SYsKcPIzlXORG3amfl3Mvlf',
        'type' => 'charge',
      ),
      'livemode' => true,
      'originating_transaction' => 'ch_3SYrQcAns9lY52GQ0c8gnLcI',
      'refunded' => false,
      'refunds' => 
      array (
        'object' => 'list',
        'data' => 
        array (
        ),
        'has_more' => false,
        'total_count' => 0,
        'url' => '/v1/application_fees/fee_1SYsKdPIzlXORG3adD0LStdW/refunds',
      ),
    ),
    'created_at' => '2026-01-07 19:14:05',
    'updated_at' => '2026-01-07 20:47:44',
  ),
  144 => 
  array (
    'id' => 145,
    'stripe_fee_id' => 'fee_1SYsKPPIzlXORG3aJlGzV1zu',
    'amount' => '3.06',
    'currency' => 'EUR',
    'created_at_stripe' => '2026-01-07 21:47:44',
    'stripe_account_id' => 'acct_1OYpemPIzlXORG3a',
    'partner_email' => 'feusrl.2019@gmail.com',
    'partner_name' => 'Feu Srl',
    'client_id' => 301,
    'charge_id' => 'py_1SYsKPPIzlXORG3akX0ZP7V4',
    'description' => 'feusrl.2019@gmail.com - acct_1OYpemPIzlXORG3a',
    'period_month' => '2025-11',
    'raw_data' => 
    array (
      'id' => 'fee_1SYsKPPIzlXORG3aJlGzV1zu',
      'object' => 'application_fee',
      'account' => 'acct_1OYpemPIzlXORG3a',
      'amount' => 306,
      'amount_refunded' => 0,
      'application' => 'ca_Ox2Rdo90ojLoUICobfcdmeRJeF2Icbzs',
      'balance_transaction' => 'txn_1SYsKRAns9lY52GQqZlVyJXs',
      'charge' => 'py_1SYsKPPIzlXORG3akX0ZP7V4',
      'created' => 1764438781,
      'currency' => 'eur',
      'fee_source' => 
      array (
        'charge' => 'py_1SYsKPPIzlXORG3akX0ZP7V4',
        'type' => 'charge',
      ),
      'livemode' => true,
      'originating_transaction' => 'ch_3SYs6OAns9lY52GQ1ATWDRAh',
      'refunded' => false,
      'refunds' => 
      array (
        'object' => 'list',
        'data' => 
        array (
        ),
        'has_more' => false,
        'total_count' => 0,
        'url' => '/v1/application_fees/fee_1SYsKPPIzlXORG3aJlGzV1zu/refunds',
      ),
    ),
    'created_at' => '2026-01-07 19:14:06',
    'updated_at' => '2026-01-07 20:47:44',
  ),
  145 => 
  array (
    'id' => 146,
    'stripe_fee_id' => 'fee_1SYsIBPMZ7tGUYNwRhxS658n',
    'amount' => '5.28',
    'currency' => 'EUR',
    'created_at_stripe' => '2026-01-07 21:47:44',
    'stripe_account_id' => 'acct_1OjmUePMZ7tGUYNw',
    'partner_email' => 'feusrl.2019@gmail.com',
    'partner_name' => 'Feu Srl',
    'client_id' => 301,
    'charge_id' => 'py_1SYsIBPMZ7tGUYNwG4FxIqSv',
    'description' => 'feusrl.2019@gmail.com - acct_1OjmUePMZ7tGUYNw',
    'period_month' => '2025-11',
    'raw_data' => 
    array (
      'id' => 'fee_1SYsIBPMZ7tGUYNwRhxS658n',
      'object' => 'application_fee',
      'account' => 'acct_1OjmUePMZ7tGUYNw',
      'amount' => 528,
      'amount_refunded' => 0,
      'application' => 'ca_Ox2Rdo90ojLoUICobfcdmeRJeF2Icbzs',
      'balance_transaction' => 'txn_1SYsIDAns9lY52GQviUA6UoE',
      'charge' => 'py_1SYsIBPMZ7tGUYNwG4FxIqSv',
      'created' => 1764438643,
      'currency' => 'eur',
      'fee_source' => 
      array (
        'charge' => 'py_1SYsIBPMZ7tGUYNwG4FxIqSv',
        'type' => 'charge',
      ),
      'livemode' => true,
      'originating_transaction' => 'ch_3SYsDcAns9lY52GQ19xC1A8u',
      'refunded' => false,
      'refunds' => 
      array (
        'object' => 'list',
        'data' => 
        array (
        ),
        'has_more' => false,
        'total_count' => 0,
        'url' => '/v1/application_fees/fee_1SYsIBPMZ7tGUYNwRhxS658n/refunds',
      ),
    ),
    'created_at' => '2026-01-07 19:14:06',
    'updated_at' => '2026-01-07 20:47:44',
  ),
  146 => 
  array (
    'id' => 147,
    'stripe_fee_id' => 'fee_1SYrj8PB8fwsTso37WofMz3O',
    'amount' => '3.45',
    'currency' => 'EUR',
    'created_at_stripe' => '2026-01-07 21:47:44',
    'stripe_account_id' => 'acct_1Oe1NCPB8fwsTso3',
    'partner_email' => 'alepizza2000@gmail.com',
    'partner_name' => 'PIZZA E TORTA DA ALEPIZZA DI BIANCHI ALESSIO',
    'client_id' => 336,
    'charge_id' => 'py_1SYrj8PB8fwsTso33zorIFeN',
    'description' => 'alepizza2000@gmail.com - acct_1Oe1NCPB8fwsTso3',
    'period_month' => '2025-11',
    'raw_data' => 
    array (
      'id' => 'fee_1SYrj8PB8fwsTso37WofMz3O',
      'object' => 'application_fee',
      'account' => 'acct_1Oe1NCPB8fwsTso3',
      'amount' => 345,
      'amount_refunded' => 0,
      'application' => 'ca_Ox2Rdo90ojLoUICobfcdmeRJeF2Icbzs',
      'balance_transaction' => 'txn_1SYrjAAns9lY52GQ8tuasnsn',
      'charge' => 'py_1SYrj8PB8fwsTso33zorIFeN',
      'created' => 1764436470,
      'currency' => 'eur',
      'fee_source' => 
      array (
        'charge' => 'py_1SYrj8PB8fwsTso33zorIFeN',
        'type' => 'charge',
      ),
      'livemode' => true,
      'originating_transaction' => 'ch_3SYquDAns9lY52GQ1lOjcuoO',
      'refunded' => false,
      'refunds' => 
      array (
        'object' => 'list',
        'data' => 
        array (
        ),
        'has_more' => false,
        'total_count' => 0,
        'url' => '/v1/application_fees/fee_1SYrj8PB8fwsTso37WofMz3O/refunds',
      ),
    ),
    'created_at' => '2026-01-07 19:14:07',
    'updated_at' => '2026-01-07 20:47:44',
  ),
  147 => 
  array (
    'id' => 148,
    'stripe_fee_id' => 'fee_1SYqzRAcaExTZKe8MEQdCyXr',
    'amount' => '3.89',
    'currency' => 'EUR',
    'created_at_stripe' => '2026-01-07 21:47:44',
    'stripe_account_id' => 'acct_1QsMHWAcaExTZKe8',
    'partner_email' => 'nclcucc@gmail.com',
    'partner_name' => 'La Loggia sul Mare SRL',
    'client_id' => 322,
    'charge_id' => 'py_1SYqzQAcaExTZKe8ssDcdEZb',
    'description' => 'nclcucc@gmail.com - acct_1QsMHWAcaExTZKe8',
    'period_month' => '2025-11',
    'raw_data' => 
    array (
      'id' => 'fee_1SYqzRAcaExTZKe8MEQdCyXr',
      'object' => 'application_fee',
      'account' => 'acct_1QsMHWAcaExTZKe8',
      'amount' => 389,
      'amount_refunded' => 0,
      'application' => 'ca_Ox2Rdo90ojLoUICobfcdmeRJeF2Icbzs',
      'balance_transaction' => 'txn_1SYqzTAns9lY52GQrjYMlMB6',
      'charge' => 'py_1SYqzQAcaExTZKe8ssDcdEZb',
      'created' => 1764433637,
      'currency' => 'eur',
      'fee_source' => 
      array (
        'charge' => 'py_1SYqzQAcaExTZKe8ssDcdEZb',
        'type' => 'charge',
      ),
      'livemode' => true,
      'originating_transaction' => 'ch_3SYqeFAns9lY52GQ1FMnoXMM',
      'refunded' => false,
      'refunds' => 
      array (
        'object' => 'list',
        'data' => 
        array (
        ),
        'has_more' => false,
        'total_count' => 0,
        'url' => '/v1/application_fees/fee_1SYqzRAcaExTZKe8MEQdCyXr/refunds',
      ),
    ),
    'created_at' => '2026-01-07 19:14:08',
    'updated_at' => '2026-01-07 20:47:44',
  ),
  148 => 
  array (
    'id' => 149,
    'stripe_fee_id' => 'fee_1SYq8HPEpkzElSu4Jl2xhaTE',
    'amount' => '2.94',
    'currency' => 'EUR',
    'created_at_stripe' => '2026-01-07 21:47:44',
    'stripe_account_id' => 'acct_1OrHzGPEpkzElSu4',
    'partner_email' => 'andreadellomodarme@gmail.com',
    'partner_name' => 'PUNTO P DI ANDREA DELL\'OMODARME',
    'client_id' => 383,
    'charge_id' => 'py_1SYq8HPEpkzElSu4B5ebmMu7',
    'description' => 'andreadellomodarme@gmail.com - acct_1OrHzGPEpkzElSu4',
    'period_month' => '2025-11',
    'raw_data' => 
    array (
      'id' => 'fee_1SYq8HPEpkzElSu4Jl2xhaTE',
      'object' => 'application_fee',
      'account' => 'acct_1OrHzGPEpkzElSu4',
      'amount' => 294,
      'amount_refunded' => 0,
      'application' => 'ca_Ox2Rdo90ojLoUICobfcdmeRJeF2Icbzs',
      'balance_transaction' => 'txn_1SYq8JAns9lY52GQbDIROJMP',
      'charge' => 'py_1SYq8HPEpkzElSu4B5ebmMu7',
      'created' => 1764430341,
      'currency' => 'eur',
      'fee_source' => 
      array (
        'charge' => 'py_1SYq8HPEpkzElSu4B5ebmMu7',
        'type' => 'charge',
      ),
      'livemode' => true,
      'originating_transaction' => 'ch_3SYq5kAns9lY52GQ0XyNzjdq',
      'refunded' => false,
      'refunds' => 
      array (
        'object' => 'list',
        'data' => 
        array (
        ),
        'has_more' => false,
        'total_count' => 0,
        'url' => '/v1/application_fees/fee_1SYq8HPEpkzElSu4Jl2xhaTE/refunds',
      ),
    ),
    'created_at' => '2026-01-07 19:14:08',
    'updated_at' => '2026-01-07 20:47:44',
  ),
  149 => 
  array (
    'id' => 150,
    'stripe_fee_id' => 'fee_1SYWQ2PFSTNU0nUGLZr1IXLX',
    'amount' => '3.08',
    'currency' => 'EUR',
    'created_at_stripe' => '2026-01-07 21:47:44',
    'stripe_account_id' => 'acct_1OtvsmPFSTNU0nUG',
    'partner_email' => 'fortipizza@gmail.com',
    'partner_name' => 'Forti Pizza e Torta',
    'client_id' => 303,
    'charge_id' => 'py_1SYWQ2PFSTNU0nUGuSTLQOD7',
    'description' => 'fortipizza@gmail.com - acct_1OtvsmPFSTNU0nUG',
    'period_month' => '2025-11',
    'raw_data' => 
    array (
      'id' => 'fee_1SYWQ2PFSTNU0nUGLZr1IXLX',
      'object' => 'application_fee',
      'account' => 'acct_1OtvsmPFSTNU0nUG',
      'amount' => 308,
      'amount_refunded' => 0,
      'application' => 'ca_Ox2Rdo90ojLoUICobfcdmeRJeF2Icbzs',
      'balance_transaction' => 'txn_1SYWQ5Ans9lY52GQYNs7ftgX',
      'charge' => 'py_1SYWQ2PFSTNU0nUGuSTLQOD7',
      'created' => 1764354562,
      'currency' => 'eur',
      'fee_source' => 
      array (
        'charge' => 'py_1SYWQ2PFSTNU0nUGuSTLQOD7',
        'type' => 'charge',
      ),
      'livemode' => true,
      'originating_transaction' => 'ch_3SYWOcAns9lY52GQ06gMXieK',
      'refunded' => false,
      'refunds' => 
      array (
        'object' => 'list',
        'data' => 
        array (
        ),
        'has_more' => false,
        'total_count' => 0,
        'url' => '/v1/application_fees/fee_1SYWQ2PFSTNU0nUGLZr1IXLX/refunds',
      ),
    ),
    'created_at' => '2026-01-07 19:14:09',
    'updated_at' => '2026-01-07 20:47:44',
  ),
  150 => 
  array (
    'id' => 151,
    'stripe_fee_id' => 'fee_1SYWOqAcaExTZKe8OSjgXhRF',
    'amount' => '3.99',
    'currency' => 'EUR',
    'created_at_stripe' => '2026-01-07 21:47:44',
    'stripe_account_id' => 'acct_1QsMHWAcaExTZKe8',
    'partner_email' => 'nclcucc@gmail.com',
    'partner_name' => 'La Loggia sul Mare SRL',
    'client_id' => 322,
    'charge_id' => 'py_1SYWOqAcaExTZKe8JKMMSm0F',
    'description' => 'nclcucc@gmail.com - acct_1QsMHWAcaExTZKe8',
    'period_month' => '2025-11',
    'raw_data' => 
    array (
      'id' => 'fee_1SYWOqAcaExTZKe8OSjgXhRF',
      'object' => 'application_fee',
      'account' => 'acct_1QsMHWAcaExTZKe8',
      'amount' => 399,
      'amount_refunded' => 0,
      'application' => 'ca_Ox2Rdo90ojLoUICobfcdmeRJeF2Icbzs',
      'balance_transaction' => 'txn_1SYWOtAns9lY52GQUhYzkz7i',
      'charge' => 'py_1SYWOqAcaExTZKe8JKMMSm0F',
      'created' => 1764354488,
      'currency' => 'eur',
      'fee_source' => 
      array (
        'charge' => 'py_1SYWOqAcaExTZKe8JKMMSm0F',
        'type' => 'charge',
      ),
      'livemode' => true,
      'originating_transaction' => 'ch_3SYW8kAns9lY52GQ0gbvdiif',
      'refunded' => false,
      'refunds' => 
      array (
        'object' => 'list',
        'data' => 
        array (
        ),
        'has_more' => false,
        'total_count' => 0,
        'url' => '/v1/application_fees/fee_1SYWOqAcaExTZKe8OSjgXhRF/refunds',
      ),
    ),
    'created_at' => '2026-01-07 19:14:10',
    'updated_at' => '2026-01-07 20:47:44',
  ),
  151 => 
  array (
    'id' => 152,
    'stripe_fee_id' => 'fee_1SYWNcPIzlXORG3aXMScAihO',
    'amount' => '5.09',
    'currency' => 'EUR',
    'created_at_stripe' => '2026-01-07 21:47:44',
    'stripe_account_id' => 'acct_1OYpemPIzlXORG3a',
    'partner_email' => 'feusrl.2019@gmail.com',
    'partner_name' => 'Feu Srl',
    'client_id' => 301,
    'charge_id' => 'py_1SYWNcPIzlXORG3alHDvbavZ',
    'description' => 'feusrl.2019@gmail.com - acct_1OYpemPIzlXORG3a',
    'period_month' => '2025-11',
    'raw_data' => 
    array (
      'id' => 'fee_1SYWNcPIzlXORG3aXMScAihO',
      'object' => 'application_fee',
      'account' => 'acct_1OYpemPIzlXORG3a',
      'amount' => 509,
      'amount_refunded' => 0,
      'application' => 'ca_Ox2Rdo90ojLoUICobfcdmeRJeF2Icbzs',
      'balance_transaction' => 'txn_1SYWNfAns9lY52GQ1VZAIXl1',
      'charge' => 'py_1SYWNcPIzlXORG3alHDvbavZ',
      'created' => 1764354412,
      'currency' => 'eur',
      'fee_source' => 
      array (
        'charge' => 'py_1SYWNcPIzlXORG3alHDvbavZ',
        'type' => 'charge',
      ),
      'livemode' => true,
      'originating_transaction' => 'ch_3SYWBwAns9lY52GQ0oEQauLK',
      'refunded' => false,
      'refunds' => 
      array (
        'object' => 'list',
        'data' => 
        array (
        ),
        'has_more' => false,
        'total_count' => 0,
        'url' => '/v1/application_fees/fee_1SYWNcPIzlXORG3aXMScAihO/refunds',
      ),
    ),
    'created_at' => '2026-01-07 19:14:10',
    'updated_at' => '2026-01-07 20:47:44',
  ),
  152 => 
  array (
    'id' => 153,
    'stripe_fee_id' => 'fee_1SYVahPCrwFqsIfa5DjTkz4v',
    'amount' => '3.28',
    'currency' => 'EUR',
    'created_at_stripe' => '2026-01-07 21:47:44',
    'stripe_account_id' => 'acct_1OhbRBPCrwFqsIfa',
    'partner_email' => 'maeva2000@inwind.it',
    'partner_name' => 'SPEEDY PIZZA DI MASSIMILIANO SILVESTRI',
    'client_id' => 358,
    'charge_id' => 'py_1SYVahPCrwFqsIfaFezSH9YX',
    'description' => 'maeva2000@inwind.it - acct_1OhbRBPCrwFqsIfa',
    'period_month' => '2025-11',
    'raw_data' => 
    array (
      'id' => 'fee_1SYVahPCrwFqsIfa5DjTkz4v',
      'object' => 'application_fee',
      'account' => 'acct_1OhbRBPCrwFqsIfa',
      'amount' => 328,
      'amount_refunded' => 0,
      'application' => 'ca_Ox2Rdo90ojLoUICobfcdmeRJeF2Icbzs',
      'balance_transaction' => 'txn_1SYVakAns9lY52GQRtZLy6f6',
      'charge' => 'py_1SYVahPCrwFqsIfaFezSH9YX',
      'created' => 1764351379,
      'currency' => 'eur',
      'fee_source' => 
      array (
        'charge' => 'py_1SYVahPCrwFqsIfaFezSH9YX',
        'type' => 'charge',
      ),
      'livemode' => true,
      'originating_transaction' => 'ch_3SYVZhAns9lY52GQ0VKxqKmj',
      'refunded' => false,
      'refunds' => 
      array (
        'object' => 'list',
        'data' => 
        array (
        ),
        'has_more' => false,
        'total_count' => 0,
        'url' => '/v1/application_fees/fee_1SYVahPCrwFqsIfa5DjTkz4v/refunds',
      ),
    ),
    'created_at' => '2026-01-07 19:14:11',
    'updated_at' => '2026-01-07 20:47:44',
  ),
  153 => 
  array (
    'id' => 154,
    'stripe_fee_id' => 'fee_1SYVXSPIzlXORG3ajXLty0sJ',
    'amount' => '4.77',
    'currency' => 'EUR',
    'created_at_stripe' => '2026-01-07 21:47:44',
    'stripe_account_id' => 'acct_1OYpemPIzlXORG3a',
    'partner_email' => 'feusrl.2019@gmail.com',
    'partner_name' => 'Feu Srl',
    'client_id' => 301,
    'charge_id' => 'py_1SYVXSPIzlXORG3aycF1tcI2',
    'description' => 'feusrl.2019@gmail.com - acct_1OYpemPIzlXORG3a',
    'period_month' => '2025-11',
    'raw_data' => 
    array (
      'id' => 'fee_1SYVXSPIzlXORG3ajXLty0sJ',
      'object' => 'application_fee',
      'account' => 'acct_1OYpemPIzlXORG3a',
      'amount' => 477,
      'amount_refunded' => 0,
      'application' => 'ca_Ox2Rdo90ojLoUICobfcdmeRJeF2Icbzs',
      'balance_transaction' => 'txn_1SYVXUAns9lY52GQguyVLH8b',
      'charge' => 'py_1SYVXSPIzlXORG3aycF1tcI2',
      'created' => 1764351178,
      'currency' => 'eur',
      'fee_source' => 
      array (
        'charge' => 'py_1SYVXSPIzlXORG3aycF1tcI2',
        'type' => 'charge',
      ),
      'livemode' => true,
      'originating_transaction' => 'ch_3SYV6YAns9lY52GQ0L9IzUHF',
      'refunded' => false,
      'refunds' => 
      array (
        'object' => 'list',
        'data' => 
        array (
        ),
        'has_more' => false,
        'total_count' => 0,
        'url' => '/v1/application_fees/fee_1SYVXSPIzlXORG3ajXLty0sJ/refunds',
      ),
    ),
    'created_at' => '2026-01-07 19:14:11',
    'updated_at' => '2026-01-07 20:47:44',
  ),
  154 => 
  array (
    'id' => 155,
    'stripe_fee_id' => 'fee_1SYUFvPEpkzElSu4LpvDKagB',
    'amount' => '2.90',
    'currency' => 'EUR',
    'created_at_stripe' => '2026-01-07 21:47:44',
    'stripe_account_id' => 'acct_1OrHzGPEpkzElSu4',
    'partner_email' => 'andreadellomodarme@gmail.com',
    'partner_name' => 'PUNTO P DI ANDREA DELL\'OMODARME',
    'client_id' => 383,
    'charge_id' => 'py_1SYUFvPEpkzElSu4QpzAfPpq',
    'description' => 'andreadellomodarme@gmail.com - acct_1OrHzGPEpkzElSu4',
    'period_month' => '2025-11',
    'raw_data' => 
    array (
      'id' => 'fee_1SYUFvPEpkzElSu4LpvDKagB',
      'object' => 'application_fee',
      'account' => 'acct_1OrHzGPEpkzElSu4',
      'amount' => 290,
      'amount_refunded' => 0,
      'application' => 'ca_Ox2Rdo90ojLoUICobfcdmeRJeF2Icbzs',
      'balance_transaction' => 'txn_1SYUFyAns9lY52GQPeIhhy0K',
      'charge' => 'py_1SYUFvPEpkzElSu4QpzAfPpq',
      'created' => 1764346247,
      'currency' => 'eur',
      'fee_source' => 
      array (
        'charge' => 'py_1SYUFvPEpkzElSu4QpzAfPpq',
        'type' => 'charge',
      ),
      'livemode' => true,
      'originating_transaction' => 'ch_3SYU7zAns9lY52GQ110954Xo',
      'refunded' => false,
      'refunds' => 
      array (
        'object' => 'list',
        'data' => 
        array (
        ),
        'has_more' => false,
        'total_count' => 0,
        'url' => '/v1/application_fees/fee_1SYUFvPEpkzElSu4LpvDKagB/refunds',
      ),
    ),
    'created_at' => '2026-01-07 19:14:12',
    'updated_at' => '2026-01-07 20:47:44',
  ),
  155 => 
  array (
    'id' => 156,
    'stripe_fee_id' => 'fee_1SYP6KPIzlXORG3a7LIJfXaS',
    'amount' => '4.76',
    'currency' => 'EUR',
    'created_at_stripe' => '2026-01-07 21:47:44',
    'stripe_account_id' => 'acct_1OYpemPIzlXORG3a',
    'partner_email' => 'feusrl.2019@gmail.com',
    'partner_name' => 'Feu Srl',
    'client_id' => 301,
    'charge_id' => 'py_1SYP6KPIzlXORG3ag7mwWLbX',
    'description' => 'feusrl.2019@gmail.com - acct_1OYpemPIzlXORG3a',
    'period_month' => '2025-11',
    'raw_data' => 
    array (
      'id' => 'fee_1SYP6KPIzlXORG3a7LIJfXaS',
      'object' => 'application_fee',
      'account' => 'acct_1OYpemPIzlXORG3a',
      'amount' => 476,
      'amount_refunded' => 0,
      'application' => 'ca_Ox2Rdo90ojLoUICobfcdmeRJeF2Icbzs',
      'balance_transaction' => 'txn_1SYP6MAns9lY52GQFOd9v7dW',
      'charge' => 'py_1SYP6KPIzlXORG3ag7mwWLbX',
      'created' => 1764326432,
      'currency' => 'eur',
      'fee_source' => 
      array (
        'charge' => 'py_1SYP6KPIzlXORG3ag7mwWLbX',
        'type' => 'charge',
      ),
      'livemode' => true,
      'originating_transaction' => 'ch_3SYNglAns9lY52GQ1ZsJ6tDD',
      'refunded' => false,
      'refunds' => 
      array (
        'object' => 'list',
        'data' => 
        array (
        ),
        'has_more' => false,
        'total_count' => 0,
        'url' => '/v1/application_fees/fee_1SYP6KPIzlXORG3a7LIJfXaS/refunds',
      ),
    ),
    'created_at' => '2026-01-07 19:14:13',
    'updated_at' => '2026-01-07 20:47:44',
  ),
  156 => 
  array (
    'id' => 157,
    'stripe_fee_id' => 'fee_1SYBaRPB8fwsTso3l29acHep',
    'amount' => '3.41',
    'currency' => 'EUR',
    'created_at_stripe' => '2026-01-07 21:47:44',
    'stripe_account_id' => 'acct_1Oe1NCPB8fwsTso3',
    'partner_email' => 'alepizza2000@gmail.com',
    'partner_name' => 'PIZZA E TORTA DA ALEPIZZA DI BIANCHI ALESSIO',
    'client_id' => 336,
    'charge_id' => 'py_1SYBaRPB8fwsTso3jNG1J8pL',
    'description' => 'alepizza2000@gmail.com - acct_1Oe1NCPB8fwsTso3',
    'period_month' => '2025-11',
    'raw_data' => 
    array (
      'id' => 'fee_1SYBaRPB8fwsTso3l29acHep',
      'object' => 'application_fee',
      'account' => 'acct_1Oe1NCPB8fwsTso3',
      'amount' => 341,
      'amount_refunded' => 0,
      'application' => 'ca_Ox2Rdo90ojLoUICobfcdmeRJeF2Icbzs',
      'balance_transaction' => 'txn_1SYBabAns9lY52GQiH1RQn7i',
      'charge' => 'py_1SYBaRPB8fwsTso3jNG1J8pL',
      'created' => 1764274483,
      'currency' => 'eur',
      'fee_source' => 
      array (
        'charge' => 'py_1SYBaRPB8fwsTso3jNG1J8pL',
        'type' => 'charge',
      ),
      'livemode' => true,
      'originating_transaction' => 'ch_3SYBY3Ans9lY52GQ1OqQj4iw',
      'refunded' => false,
      'refunds' => 
      array (
        'object' => 'list',
        'data' => 
        array (
        ),
        'has_more' => false,
        'total_count' => 0,
        'url' => '/v1/application_fees/fee_1SYBaRPB8fwsTso3l29acHep/refunds',
      ),
    ),
    'created_at' => '2026-01-07 19:14:13',
    'updated_at' => '2026-01-07 20:47:44',
  ),
  157 => 
  array (
    'id' => 158,
    'stripe_fee_id' => 'fee_1SY7hKPFSTNU0nUGYlp4Eekb',
    'amount' => '3.45',
    'currency' => 'EUR',
    'created_at_stripe' => '2026-01-07 21:47:44',
    'stripe_account_id' => 'acct_1OtvsmPFSTNU0nUG',
    'partner_email' => 'fortipizza@gmail.com',
    'partner_name' => 'Forti Pizza e Torta',
    'client_id' => 303,
    'charge_id' => 'py_1SY7hKPFSTNU0nUGllgRN9Im',
    'description' => 'fortipizza@gmail.com - acct_1OtvsmPFSTNU0nUG',
    'period_month' => '2025-11',
    'raw_data' => 
    array (
      'id' => 'fee_1SY7hKPFSTNU0nUGYlp4Eekb',
      'object' => 'application_fee',
      'account' => 'acct_1OtvsmPFSTNU0nUG',
      'amount' => 345,
      'amount_refunded' => 0,
      'application' => 'ca_Ox2Rdo90ojLoUICobfcdmeRJeF2Icbzs',
      'balance_transaction' => 'txn_1SY7hMAns9lY52GQpQblZKzF',
      'charge' => 'py_1SY7hKPFSTNU0nUGllgRN9Im',
      'created' => 1764259534,
      'currency' => 'eur',
      'fee_source' => 
      array (
        'charge' => 'py_1SY7hKPFSTNU0nUGllgRN9Im',
        'type' => 'charge',
      ),
      'livemode' => true,
      'originating_transaction' => 'ch_3SY7EhAns9lY52GQ1iLPWour',
      'refunded' => false,
      'refunds' => 
      array (
        'object' => 'list',
        'data' => 
        array (
        ),
        'has_more' => false,
        'total_count' => 0,
        'url' => '/v1/application_fees/fee_1SY7hKPFSTNU0nUGYlp4Eekb/refunds',
      ),
    ),
    'created_at' => '2026-01-07 19:14:14',
    'updated_at' => '2026-01-07 20:47:44',
  ),
  158 => 
  array (
    'id' => 159,
    'stripe_fee_id' => 'fee_1SXmnTPEpkzElSu48uIwUmJw',
    'amount' => '2.87',
    'currency' => 'EUR',
    'created_at_stripe' => '2026-01-07 21:47:44',
    'stripe_account_id' => 'acct_1OrHzGPEpkzElSu4',
    'partner_email' => 'andreadellomodarme@gmail.com',
    'partner_name' => 'PUNTO P DI ANDREA DELL\'OMODARME',
    'client_id' => 383,
    'charge_id' => 'py_1SXmnSPEpkzElSu4ru9jScgl',
    'description' => 'andreadellomodarme@gmail.com - acct_1OrHzGPEpkzElSu4',
    'period_month' => '2025-11',
    'raw_data' => 
    array (
      'id' => 'fee_1SXmnTPEpkzElSu48uIwUmJw',
      'object' => 'application_fee',
      'account' => 'acct_1OrHzGPEpkzElSu4',
      'amount' => 287,
      'amount_refunded' => 0,
      'application' => 'ca_Ox2Rdo90ojLoUICobfcdmeRJeF2Icbzs',
      'balance_transaction' => 'txn_1SXmnVAns9lY52GQWDa2C2qR',
      'charge' => 'py_1SXmnSPEpkzElSu4ru9jScgl',
      'created' => 1764179191,
      'currency' => 'eur',
      'fee_source' => 
      array (
        'charge' => 'py_1SXmnSPEpkzElSu4ru9jScgl',
        'type' => 'charge',
      ),
      'livemode' => true,
      'originating_transaction' => 'ch_3SXmmTAns9lY52GQ0RHEmG6X',
      'refunded' => false,
      'refunds' => 
      array (
        'object' => 'list',
        'data' => 
        array (
        ),
        'has_more' => false,
        'total_count' => 0,
        'url' => '/v1/application_fees/fee_1SXmnTPEpkzElSu48uIwUmJw/refunds',
      ),
    ),
    'created_at' => '2026-01-07 19:14:15',
    'updated_at' => '2026-01-07 20:47:44',
  ),
  159 => 
  array (
    'id' => 160,
    'stripe_fee_id' => 'fee_1SXmTTPB7qjhlfVat32lI1mc',
    'amount' => '3.37',
    'currency' => 'EUR',
    'created_at_stripe' => '2026-01-07 21:47:44',
    'stripe_account_id' => 'acct_1OrlPGPB7qjhlfVa',
    'partner_email' => 'laboratoriodellapizza@gmail.com',
    'partner_name' => 'Macrì Domenico',
    'client_id' => 325,
    'charge_id' => 'py_1SXmTTPB7qjhlfVa47YehH2O',
    'description' => 'laboratoriodellapizza@gmail.com - acct_1OrlPGPB7qjhlfVa',
    'period_month' => '2025-11',
    'raw_data' => 
    array (
      'id' => 'fee_1SXmTTPB7qjhlfVat32lI1mc',
      'object' => 'application_fee',
      'account' => 'acct_1OrlPGPB7qjhlfVa',
      'amount' => 337,
      'amount_refunded' => 0,
      'application' => 'ca_Ox2Rdo90ojLoUICobfcdmeRJeF2Icbzs',
      'balance_transaction' => 'txn_1SXmTWAns9lY52GQjiFELKp6',
      'charge' => 'py_1SXmTTPB7qjhlfVa47YehH2O',
      'created' => 1764177951,
      'currency' => 'eur',
      'fee_source' => 
      array (
        'charge' => 'py_1SXmTTPB7qjhlfVa47YehH2O',
        'type' => 'charge',
      ),
      'livemode' => true,
      'originating_transaction' => 'ch_3SXmSqAns9lY52GQ1lSC8DsF',
      'refunded' => false,
      'refunds' => 
      array (
        'object' => 'list',
        'data' => 
        array (
        ),
        'has_more' => false,
        'total_count' => 0,
        'url' => '/v1/application_fees/fee_1SXmTTPB7qjhlfVat32lI1mc/refunds',
      ),
    ),
    'created_at' => '2026-01-07 19:14:15',
    'updated_at' => '2026-01-07 20:47:44',
  ),
  160 => 
  array (
    'id' => 161,
    'stripe_fee_id' => 'fee_1SXRCHPIzlXORG3a6Q9C62aM',
    'amount' => '5.41',
    'currency' => 'EUR',
    'created_at_stripe' => '2026-01-07 21:47:44',
    'stripe_account_id' => 'acct_1OYpemPIzlXORG3a',
    'partner_email' => 'feusrl.2019@gmail.com',
    'partner_name' => 'Feu Srl',
    'client_id' => 301,
    'charge_id' => 'py_1SXRCHPIzlXORG3akMEj4Ty2',
    'description' => 'feusrl.2019@gmail.com - acct_1OYpemPIzlXORG3a',
    'period_month' => '2025-11',
    'raw_data' => 
    array (
      'id' => 'fee_1SXRCHPIzlXORG3a6Q9C62aM',
      'object' => 'application_fee',
      'account' => 'acct_1OYpemPIzlXORG3a',
      'amount' => 541,
      'amount_refunded' => 0,
      'application' => 'ca_Ox2Rdo90ojLoUICobfcdmeRJeF2Icbzs',
      'balance_transaction' => 'txn_1SXRCJAns9lY52GQ7gJxWzbj',
      'charge' => 'py_1SXRCHPIzlXORG3akMEj4Ty2',
      'created' => 1764096161,
      'currency' => 'eur',
      'fee_source' => 
      array (
        'charge' => 'py_1SXRCHPIzlXORG3akMEj4Ty2',
        'type' => 'charge',
      ),
      'livemode' => true,
      'originating_transaction' => 'ch_3SXRAMAns9lY52GQ0KVouUXf',
      'refunded' => false,
      'refunds' => 
      array (
        'object' => 'list',
        'data' => 
        array (
        ),
        'has_more' => false,
        'total_count' => 0,
        'url' => '/v1/application_fees/fee_1SXRCHPIzlXORG3a6Q9C62aM/refunds',
      ),
    ),
    'created_at' => '2026-01-07 19:14:16',
    'updated_at' => '2026-01-07 20:47:44',
  ),
  161 => 
  array (
    'id' => 162,
    'stripe_fee_id' => 'fee_1SXPE3PEpkzElSu4Fehyvorc',
    'amount' => '4.50',
    'currency' => 'EUR',
    'created_at_stripe' => '2026-01-07 21:47:44',
    'stripe_account_id' => 'acct_1OrHzGPEpkzElSu4',
    'partner_email' => 'andreadellomodarme@gmail.com',
    'partner_name' => 'PUNTO P DI ANDREA DELL\'OMODARME',
    'client_id' => 383,
    'charge_id' => 'py_1SXPE3PEpkzElSu45BWz9DlA',
    'description' => 'andreadellomodarme@gmail.com - acct_1OrHzGPEpkzElSu4',
    'period_month' => '2025-11',
    'raw_data' => 
    array (
      'id' => 'fee_1SXPE3PEpkzElSu4Fehyvorc',
      'object' => 'application_fee',
      'account' => 'acct_1OrHzGPEpkzElSu4',
      'amount' => 450,
      'amount_refunded' => 0,
      'application' => 'ca_Ox2Rdo90ojLoUICobfcdmeRJeF2Icbzs',
      'balance_transaction' => 'txn_1SXPE6Ans9lY52GQJ6hgmG44',
      'charge' => 'py_1SXPE3PEpkzElSu45BWz9DlA',
      'created' => 1764088583,
      'currency' => 'eur',
      'fee_source' => 
      array (
        'charge' => 'py_1SXPE3PEpkzElSu45BWz9DlA',
        'type' => 'charge',
      ),
      'livemode' => true,
      'originating_transaction' => 'ch_3SXPDQAns9lY52GQ08Q8ql0V',
      'refunded' => false,
      'refunds' => 
      array (
        'object' => 'list',
        'data' => 
        array (
        ),
        'has_more' => false,
        'total_count' => 0,
        'url' => '/v1/application_fees/fee_1SXPE3PEpkzElSu4Fehyvorc/refunds',
      ),
    ),
    'created_at' => '2026-01-07 19:14:16',
    'updated_at' => '2026-01-07 20:47:44',
  ),
  162 => 
  array (
    'id' => 163,
    'stripe_fee_id' => 'fee_1SXLhKArGwCSIIveTaJOf6w6',
    'amount' => '2.82',
    'currency' => 'EUR',
    'created_at_stripe' => '2026-01-07 21:47:44',
    'stripe_account_id' => 'acct_1SRyIkArGwCSIIve',
    'partner_email' => 'ordinazioni@sbriciolopizza.it',
    'partner_name' => 'PACIFIC JAFFE S.R.L.',
    'client_id' => 333,
    'charge_id' => 'py_1SXLhKArGwCSIIveYb4dOiAW',
    'description' => 'ordinazioni@sbriciolopizza.it - acct_1SRyIkArGwCSIIve',
    'period_month' => '2025-11',
    'raw_data' => 
    array (
      'id' => 'fee_1SXLhKArGwCSIIveTaJOf6w6',
      'object' => 'application_fee',
      'account' => 'acct_1SRyIkArGwCSIIve',
      'amount' => 282,
      'amount_refunded' => 0,
      'application' => 'ca_Ox2Rdo90ojLoUICobfcdmeRJeF2Icbzs',
      'balance_transaction' => 'txn_1SXLhNAns9lY52GQyGU1XS94',
      'charge' => 'py_1SXLhKArGwCSIIveYb4dOiAW',
      'created' => 1764075022,
      'currency' => 'eur',
      'fee_source' => 
      array (
        'charge' => 'py_1SXLhKArGwCSIIveYb4dOiAW',
        'type' => 'charge',
      ),
      'livemode' => true,
      'originating_transaction' => 'ch_3SXLRhAns9lY52GQ0r316hyk',
      'refunded' => false,
      'refunds' => 
      array (
        'object' => 'list',
        'data' => 
        array (
        ),
        'has_more' => false,
        'total_count' => 0,
        'url' => '/v1/application_fees/fee_1SXLhKArGwCSIIveTaJOf6w6/refunds',
      ),
    ),
    'created_at' => '2026-01-07 19:14:17',
    'updated_at' => '2026-01-07 20:47:44',
  ),
  163 => 
  array (
    'id' => 164,
    'stripe_fee_id' => 'fee_1SWfx2PEpkzElSu43Is1glxk',
    'amount' => '2.96',
    'currency' => 'EUR',
    'created_at_stripe' => '2026-01-07 21:47:44',
    'stripe_account_id' => 'acct_1OrHzGPEpkzElSu4',
    'partner_email' => 'andreadellomodarme@gmail.com',
    'partner_name' => 'PUNTO P DI ANDREA DELL\'OMODARME',
    'client_id' => 383,
    'charge_id' => 'py_1SWfx2PEpkzElSu4pz5WG5Yj',
    'description' => 'andreadellomodarme@gmail.com - acct_1OrHzGPEpkzElSu4',
    'period_month' => '2025-11',
    'raw_data' => 
    array (
      'id' => 'fee_1SWfx2PEpkzElSu43Is1glxk',
      'object' => 'application_fee',
      'account' => 'acct_1OrHzGPEpkzElSu4',
      'amount' => 296,
      'amount_refunded' => 0,
      'application' => 'ca_Ox2Rdo90ojLoUICobfcdmeRJeF2Icbzs',
      'balance_transaction' => 'txn_1SWfx5Ans9lY52GQa2y4FGeB',
      'charge' => 'py_1SWfx2PEpkzElSu4pz5WG5Yj',
      'created' => 1763914548,
      'currency' => 'eur',
      'fee_source' => 
      array (
        'charge' => 'py_1SWfx2PEpkzElSu4pz5WG5Yj',
        'type' => 'charge',
      ),
      'livemode' => true,
      'originating_transaction' => 'ch_3SWfvjAns9lY52GQ1uNqaVyi',
      'refunded' => false,
      'refunds' => 
      array (
        'object' => 'list',
        'data' => 
        array (
        ),
        'has_more' => false,
        'total_count' => 0,
        'url' => '/v1/application_fees/fee_1SWfx2PEpkzElSu43Is1glxk/refunds',
      ),
    ),
    'created_at' => '2026-01-07 19:14:17',
    'updated_at' => '2026-01-07 20:47:44',
  ),
  164 => 
  array (
    'id' => 165,
    'stripe_fee_id' => 'fee_1SWNcaPNB3k6tHL858vM1QmB',
    'amount' => '4.05',
    'currency' => 'EUR',
    'created_at_stripe' => '2026-01-07 21:47:44',
    'stripe_account_id' => 'acct_1RYoXnPNB3k6tHL8',
    'partner_email' => 'pizzeriaitrecanti@gmail.com',
    'partner_name' => 'Pizzeria I 3 Canti di Bibbiani Mirco & C. SAS',
    'client_id' => 340,
    'charge_id' => 'py_1SWNcaPNB3k6tHL8omoUfiAE',
    'description' => 'pizzeriaitrecanti@gmail.com - acct_1RYoXnPNB3k6tHL8',
    'period_month' => '2025-11',
    'raw_data' => 
    array (
      'id' => 'fee_1SWNcaPNB3k6tHL858vM1QmB',
      'object' => 'application_fee',
      'account' => 'acct_1RYoXnPNB3k6tHL8',
      'amount' => 405,
      'amount_refunded' => 0,
      'application' => 'ca_Ox2Rdo90ojLoUICobfcdmeRJeF2Icbzs',
      'balance_transaction' => 'txn_1SWNccAns9lY52GQ23GMGn5S',
      'charge' => 'py_1SWNcaPNB3k6tHL8omoUfiAE',
      'created' => 1763844088,
      'currency' => 'eur',
      'fee_source' => 
      array (
        'charge' => 'py_1SWNcaPNB3k6tHL8omoUfiAE',
        'type' => 'charge',
      ),
      'livemode' => true,
      'originating_transaction' => 'ch_3SWMIGAns9lY52GQ11TOvgVq',
      'refunded' => false,
      'refunds' => 
      array (
        'object' => 'list',
        'data' => 
        array (
        ),
        'has_more' => false,
        'total_count' => 0,
        'url' => '/v1/application_fees/fee_1SWNcaPNB3k6tHL858vM1QmB/refunds',
      ),
    ),
    'created_at' => '2026-01-07 19:14:18',
    'updated_at' => '2026-01-07 20:47:44',
  ),
  165 => 
  array (
    'id' => 166,
    'stripe_fee_id' => 'fee_1SWLUlPIzlXORG3aAo85DgAd',
    'amount' => '4.68',
    'currency' => 'EUR',
    'created_at_stripe' => '2026-01-07 21:47:44',
    'stripe_account_id' => 'acct_1OYpemPIzlXORG3a',
    'partner_email' => 'feusrl.2019@gmail.com',
    'partner_name' => 'Feu Srl',
    'client_id' => 301,
    'charge_id' => 'py_1SWLUlPIzlXORG3a1x7JiFGh',
    'description' => 'feusrl.2019@gmail.com - acct_1OYpemPIzlXORG3a',
    'period_month' => '2025-11',
    'raw_data' => 
    array (
      'id' => 'fee_1SWLUlPIzlXORG3aAo85DgAd',
      'object' => 'application_fee',
      'account' => 'acct_1OYpemPIzlXORG3a',
      'amount' => 468,
      'amount_refunded' => 0,
      'application' => 'ca_Ox2Rdo90ojLoUICobfcdmeRJeF2Icbzs',
      'balance_transaction' => 'txn_1SWLUnAns9lY52GQJ573mdf1',
      'charge' => 'py_1SWLUlPIzlXORG3a1x7JiFGh',
      'created' => 1763835915,
      'currency' => 'eur',
      'fee_source' => 
      array (
        'charge' => 'py_1SWLUlPIzlXORG3a1x7JiFGh',
        'type' => 'charge',
      ),
      'livemode' => true,
      'originating_transaction' => 'ch_3SWLLMAns9lY52GQ1ol0VsCd',
      'refunded' => false,
      'refunds' => 
      array (
        'object' => 'list',
        'data' => 
        array (
        ),
        'has_more' => false,
        'total_count' => 0,
        'url' => '/v1/application_fees/fee_1SWLUlPIzlXORG3aAo85DgAd/refunds',
      ),
    ),
    'created_at' => '2026-01-07 19:14:19',
    'updated_at' => '2026-01-07 20:47:44',
  ),
  166 => 
  array (
    'id' => 167,
    'stripe_fee_id' => 'fee_1SWLBiPAESt8veHwmkGHRDkh',
    'amount' => '2.82',
    'currency' => 'EUR',
    'created_at_stripe' => '2026-01-07 21:47:44',
    'stripe_account_id' => 'acct_1ROCdFPAESt8veHw',
    'partner_email' => 'anticatradizione1950@gmail.com',
    'partner_name' => 'Osteria Antica Tradizione srls.',
    'client_id' => 332,
    'charge_id' => 'py_1SWLBiPAESt8veHwB04ewhn9',
    'description' => 'anticatradizione1950@gmail.com - acct_1ROCdFPAESt8veHw',
    'period_month' => '2025-11',
    'raw_data' => 
    array (
      'id' => 'fee_1SWLBiPAESt8veHwmkGHRDkh',
      'object' => 'application_fee',
      'account' => 'acct_1ROCdFPAESt8veHw',
      'amount' => 282,
      'amount_refunded' => 0,
      'application' => 'ca_Ox2Rdo90ojLoUICobfcdmeRJeF2Icbzs',
      'balance_transaction' => 'txn_1SWLBlAns9lY52GQR3lVxYJe',
      'charge' => 'py_1SWLBiPAESt8veHwB04ewhn9',
      'created' => 1763834734,
      'currency' => 'eur',
      'fee_source' => 
      array (
        'charge' => 'py_1SWLBiPAESt8veHwB04ewhn9',
        'type' => 'charge',
      ),
      'livemode' => true,
      'originating_transaction' => 'ch_3SWKJhAns9lY52GQ0ABHWvCA',
      'refunded' => false,
      'refunds' => 
      array (
        'object' => 'list',
        'data' => 
        array (
        ),
        'has_more' => false,
        'total_count' => 0,
        'url' => '/v1/application_fees/fee_1SWLBiPAESt8veHwmkGHRDkh/refunds',
      ),
    ),
    'created_at' => '2026-01-07 19:14:20',
    'updated_at' => '2026-01-07 20:47:44',
  ),
  167 => 
  array (
    'id' => 168,
    'stripe_fee_id' => 'fee_1SWL5TPOk3iRdpD8owLy5NbD',
    'amount' => '3.09',
    'currency' => 'EUR',
    'created_at_stripe' => '2026-01-07 21:47:44',
    'stripe_account_id' => 'acct_1Qex9uPOk3iRdpD8',
    'partner_email' => 'ilmurettosnc@gmail.com',
    'partner_name' => 'IL MURETTO SNC DI COLUCCI DOMENICO E CECCHI JONATHAN',
    'client_id' => 309,
    'charge_id' => 'py_1SWL5TPOk3iRdpD8wTNOucko',
    'description' => 'ilmurettosnc@gmail.com - acct_1Qex9uPOk3iRdpD8',
    'period_month' => '2025-11',
    'raw_data' => 
    array (
      'id' => 'fee_1SWL5TPOk3iRdpD8owLy5NbD',
      'object' => 'application_fee',
      'account' => 'acct_1Qex9uPOk3iRdpD8',
      'amount' => 309,
      'amount_refunded' => 0,
      'application' => 'ca_Ox2Rdo90ojLoUICobfcdmeRJeF2Icbzs',
      'balance_transaction' => 'txn_1SWL5VAns9lY52GQtAYY9cWR',
      'charge' => 'py_1SWL5TPOk3iRdpD8wTNOucko',
      'created' => 1763834347,
      'currency' => 'eur',
      'fee_source' => 
      array (
        'charge' => 'py_1SWL5TPOk3iRdpD8wTNOucko',
        'type' => 'charge',
      ),
      'livemode' => true,
      'originating_transaction' => 'ch_3SWL4xAns9lY52GQ0HWQz5CQ',
      'refunded' => false,
      'refunds' => 
      array (
        'object' => 'list',
        'data' => 
        array (
        ),
        'has_more' => false,
        'total_count' => 0,
        'url' => '/v1/application_fees/fee_1SWL5TPOk3iRdpD8owLy5NbD/refunds',
      ),
    ),
    'created_at' => '2026-01-07 19:14:20',
    'updated_at' => '2026-01-07 20:47:44',
  ),
  168 => 
  array (
    'id' => 169,
    'stripe_fee_id' => 'fee_1SWL3FPIzlXORG3a3VH2uJWu',
    'amount' => '4.93',
    'currency' => 'EUR',
    'created_at_stripe' => '2026-01-07 21:47:44',
    'stripe_account_id' => 'acct_1OYpemPIzlXORG3a',
    'partner_email' => 'feusrl.2019@gmail.com',
    'partner_name' => 'Feu Srl',
    'client_id' => 301,
    'charge_id' => 'py_1SWL3FPIzlXORG3at3w4cKl7',
    'description' => 'feusrl.2019@gmail.com - acct_1OYpemPIzlXORG3a',
    'period_month' => '2025-11',
    'raw_data' => 
    array (
      'id' => 'fee_1SWL3FPIzlXORG3a3VH2uJWu',
      'object' => 'application_fee',
      'account' => 'acct_1OYpemPIzlXORG3a',
      'amount' => 493,
      'amount_refunded' => 0,
      'application' => 'ca_Ox2Rdo90ojLoUICobfcdmeRJeF2Icbzs',
      'balance_transaction' => 'txn_1SWL3HAns9lY52GQkTcvCkxX',
      'charge' => 'py_1SWL3FPIzlXORG3at3w4cKl7',
      'created' => 1763834209,
      'currency' => 'eur',
      'fee_source' => 
      array (
        'charge' => 'py_1SWL3FPIzlXORG3at3w4cKl7',
        'type' => 'charge',
      ),
      'livemode' => true,
      'originating_transaction' => 'ch_3SWL2WAns9lY52GQ0J6Lz8Hl',
      'refunded' => false,
      'refunds' => 
      array (
        'object' => 'list',
        'data' => 
        array (
        ),
        'has_more' => false,
        'total_count' => 0,
        'url' => '/v1/application_fees/fee_1SWL3FPIzlXORG3a3VH2uJWu/refunds',
      ),
    ),
    'created_at' => '2026-01-07 19:14:21',
    'updated_at' => '2026-01-07 20:47:44',
  ),
  169 => 
  array (
    'id' => 170,
    'stripe_fee_id' => 'fee_1SWKwXPB7qjhlfVawQ6dwnUm',
    'amount' => '3.02',
    'currency' => 'EUR',
    'created_at_stripe' => '2026-01-07 21:47:44',
    'stripe_account_id' => 'acct_1OrlPGPB7qjhlfVa',
    'partner_email' => 'laboratoriodellapizza@gmail.com',
    'partner_name' => 'Macrì Domenico',
    'client_id' => 325,
    'charge_id' => 'py_1SWKwXPB7qjhlfVab8LVLXRg',
    'description' => 'laboratoriodellapizza@gmail.com - acct_1OrlPGPB7qjhlfVa',
    'period_month' => '2025-11',
    'raw_data' => 
    array (
      'id' => 'fee_1SWKwXPB7qjhlfVawQ6dwnUm',
      'object' => 'application_fee',
      'account' => 'acct_1OrlPGPB7qjhlfVa',
      'amount' => 302,
      'amount_refunded' => 0,
      'application' => 'ca_Ox2Rdo90ojLoUICobfcdmeRJeF2Icbzs',
      'balance_transaction' => 'txn_1SWKwaAns9lY52GQpj32qV0U',
      'charge' => 'py_1SWKwXPB7qjhlfVab8LVLXRg',
      'created' => 1763833793,
      'currency' => 'eur',
      'fee_source' => 
      array (
        'charge' => 'py_1SWKwXPB7qjhlfVab8LVLXRg',
        'type' => 'charge',
      ),
      'livemode' => true,
      'originating_transaction' => 'ch_3SWKvpAns9lY52GQ1Y2mCHHb',
      'refunded' => false,
      'refunds' => 
      array (
        'object' => 'list',
        'data' => 
        array (
        ),
        'has_more' => false,
        'total_count' => 0,
        'url' => '/v1/application_fees/fee_1SWKwXPB7qjhlfVawQ6dwnUm/refunds',
      ),
    ),
    'created_at' => '2026-01-07 19:14:22',
    'updated_at' => '2026-01-07 20:47:44',
  ),
  170 => 
  array (
    'id' => 171,
    'stripe_fee_id' => 'fee_1SWKojPB8fwsTso3SPT5DJSi',
    'amount' => '2.89',
    'currency' => 'EUR',
    'created_at_stripe' => '2026-01-07 21:47:44',
    'stripe_account_id' => 'acct_1Oe1NCPB8fwsTso3',
    'partner_email' => 'alepizza2000@gmail.com',
    'partner_name' => 'PIZZA E TORTA DA ALEPIZZA DI BIANCHI ALESSIO',
    'client_id' => 336,
    'charge_id' => 'py_1SWKoiPB8fwsTso3fCK4fScW',
    'description' => 'alepizza2000@gmail.com - acct_1Oe1NCPB8fwsTso3',
    'period_month' => '2025-11',
    'raw_data' => 
    array (
      'id' => 'fee_1SWKojPB8fwsTso3SPT5DJSi',
      'object' => 'application_fee',
      'account' => 'acct_1Oe1NCPB8fwsTso3',
      'amount' => 289,
      'amount_refunded' => 0,
      'application' => 'ca_Ox2Rdo90ojLoUICobfcdmeRJeF2Icbzs',
      'balance_transaction' => 'txn_1SWKolAns9lY52GQDAJPh5Iy',
      'charge' => 'py_1SWKoiPB8fwsTso3fCK4fScW',
      'created' => 1763833309,
      'currency' => 'eur',
      'fee_source' => 
      array (
        'charge' => 'py_1SWKoiPB8fwsTso3fCK4fScW',
        'type' => 'charge',
      ),
      'livemode' => true,
      'originating_transaction' => 'ch_3SWKmgAns9lY52GQ07t6PsyL',
      'refunded' => false,
      'refunds' => 
      array (
        'object' => 'list',
        'data' => 
        array (
        ),
        'has_more' => false,
        'total_count' => 0,
        'url' => '/v1/application_fees/fee_1SWKojPB8fwsTso3SPT5DJSi/refunds',
      ),
    ),
    'created_at' => '2026-01-07 19:14:23',
    'updated_at' => '2026-01-07 20:47:44',
  ),
  171 => 
  array (
    'id' => 172,
    'stripe_fee_id' => 'fee_1SWKHMPEpkzElSu4SW2n47Ue',
    'amount' => '3.18',
    'currency' => 'EUR',
    'created_at_stripe' => '2026-01-07 21:47:44',
    'stripe_account_id' => 'acct_1OrHzGPEpkzElSu4',
    'partner_email' => 'andreadellomodarme@gmail.com',
    'partner_name' => 'PUNTO P DI ANDREA DELL\'OMODARME',
    'client_id' => 383,
    'charge_id' => 'py_1SWKHMPEpkzElSu4r7sM8Emr',
    'description' => 'andreadellomodarme@gmail.com - acct_1OrHzGPEpkzElSu4',
    'period_month' => '2025-11',
    'raw_data' => 
    array (
      'id' => 'fee_1SWKHMPEpkzElSu4SW2n47Ue',
      'object' => 'application_fee',
      'account' => 'acct_1OrHzGPEpkzElSu4',
      'amount' => 318,
      'amount_refunded' => 0,
      'application' => 'ca_Ox2Rdo90ojLoUICobfcdmeRJeF2Icbzs',
      'balance_transaction' => 'txn_1SWKHOAns9lY52GQt7f0CabA',
      'charge' => 'py_1SWKHMPEpkzElSu4r7sM8Emr',
      'created' => 1763831240,
      'currency' => 'eur',
      'fee_source' => 
      array (
        'charge' => 'py_1SWKHMPEpkzElSu4r7sM8Emr',
        'type' => 'charge',
      ),
      'livemode' => true,
      'originating_transaction' => 'ch_3SWKGiAns9lY52GQ04CUb4sQ',
      'refunded' => false,
      'refunds' => 
      array (
        'object' => 'list',
        'data' => 
        array (
        ),
        'has_more' => false,
        'total_count' => 0,
        'url' => '/v1/application_fees/fee_1SWKHMPEpkzElSu4SW2n47Ue/refunds',
      ),
    ),
    'created_at' => '2026-01-07 19:14:23',
    'updated_at' => '2026-01-07 20:47:44',
  ),
  172 => 
  array (
    'id' => 173,
    'stripe_fee_id' => 'fee_1SWKC1PFSTNU0nUG79vQq1FQ',
    'amount' => '3.55',
    'currency' => 'EUR',
    'created_at_stripe' => '2026-01-07 21:47:44',
    'stripe_account_id' => 'acct_1OtvsmPFSTNU0nUG',
    'partner_email' => 'fortipizza@gmail.com',
    'partner_name' => 'Forti Pizza e Torta',
    'client_id' => 303,
    'charge_id' => 'py_1SWKC1PFSTNU0nUGZrtekBQq',
    'description' => 'fortipizza@gmail.com - acct_1OtvsmPFSTNU0nUG',
    'period_month' => '2025-11',
    'raw_data' => 
    array (
      'id' => 'fee_1SWKC1PFSTNU0nUG79vQq1FQ',
      'object' => 'application_fee',
      'account' => 'acct_1OtvsmPFSTNU0nUG',
      'amount' => 355,
      'amount_refunded' => 0,
      'application' => 'ca_Ox2Rdo90ojLoUICobfcdmeRJeF2Icbzs',
      'balance_transaction' => 'txn_1SWKC4Ans9lY52GQymyzs5vd',
      'charge' => 'py_1SWKC1PFSTNU0nUGZrtekBQq',
      'created' => 1763830909,
      'currency' => 'eur',
      'fee_source' => 
      array (
        'charge' => 'py_1SWKC1PFSTNU0nUGZrtekBQq',
        'type' => 'charge',
      ),
      'livemode' => true,
      'originating_transaction' => 'ch_3SWKAeAns9lY52GQ0Mjn38Qh',
      'refunded' => false,
      'refunds' => 
      array (
        'object' => 'list',
        'data' => 
        array (
        ),
        'has_more' => false,
        'total_count' => 0,
        'url' => '/v1/application_fees/fee_1SWKC1PFSTNU0nUG79vQq1FQ/refunds',
      ),
    ),
    'created_at' => '2026-01-07 19:14:24',
    'updated_at' => '2026-01-07 20:47:44',
  ),
  173 => 
  array (
    'id' => 174,
    'stripe_fee_id' => 'fee_1SWHpZPEpkzElSu4VTNJrow8',
    'amount' => '4.00',
    'currency' => 'EUR',
    'created_at_stripe' => '2026-01-07 21:47:44',
    'stripe_account_id' => 'acct_1OrHzGPEpkzElSu4',
    'partner_email' => 'andreadellomodarme@gmail.com',
    'partner_name' => 'PUNTO P DI ANDREA DELL\'OMODARME',
    'client_id' => 383,
    'charge_id' => 'py_1SWHpZPEpkzElSu468DZqbF3',
    'description' => 'andreadellomodarme@gmail.com - acct_1OrHzGPEpkzElSu4',
    'period_month' => '2025-11',
    'raw_data' => 
    array (
      'id' => 'fee_1SWHpZPEpkzElSu4VTNJrow8',
      'object' => 'application_fee',
      'account' => 'acct_1OrHzGPEpkzElSu4',
      'amount' => 400,
      'amount_refunded' => 0,
      'application' => 'ca_Ox2Rdo90ojLoUICobfcdmeRJeF2Icbzs',
      'balance_transaction' => 'txn_1SWHpcAns9lY52GQG6pBUvSp',
      'charge' => 'py_1SWHpZPEpkzElSu468DZqbF3',
      'created' => 1763821829,
      'currency' => 'eur',
      'fee_source' => 
      array (
        'charge' => 'py_1SWHpZPEpkzElSu468DZqbF3',
        'type' => 'charge',
      ),
      'livemode' => true,
      'originating_transaction' => 'ch_3SWHjxAns9lY52GQ03NgZf2Q',
      'refunded' => false,
      'refunds' => 
      array (
        'object' => 'list',
        'data' => 
        array (
        ),
        'has_more' => false,
        'total_count' => 0,
        'url' => '/v1/application_fees/fee_1SWHpZPEpkzElSu4VTNJrow8/refunds',
      ),
    ),
    'created_at' => '2026-01-07 19:14:25',
    'updated_at' => '2026-01-07 20:47:44',
  ),
  174 => 
  array (
    'id' => 175,
    'stripe_fee_id' => 'fee_1SWHn2PEpkzElSu4aBTkXqDR',
    'amount' => '3.19',
    'currency' => 'EUR',
    'created_at_stripe' => '2026-01-07 21:47:44',
    'stripe_account_id' => 'acct_1OrHzGPEpkzElSu4',
    'partner_email' => 'andreadellomodarme@gmail.com',
    'partner_name' => 'PUNTO P DI ANDREA DELL\'OMODARME',
    'client_id' => 383,
    'charge_id' => 'py_1SWHn2PEpkzElSu4u7LVWmUK',
    'description' => 'andreadellomodarme@gmail.com - acct_1OrHzGPEpkzElSu4',
    'period_month' => '2025-11',
    'raw_data' => 
    array (
      'id' => 'fee_1SWHn2PEpkzElSu4aBTkXqDR',
      'object' => 'application_fee',
      'account' => 'acct_1OrHzGPEpkzElSu4',
      'amount' => 319,
      'amount_refunded' => 0,
      'application' => 'ca_Ox2Rdo90ojLoUICobfcdmeRJeF2Icbzs',
      'balance_transaction' => 'txn_1SWHn4Ans9lY52GQDVwyEeLC',
      'charge' => 'py_1SWHn2PEpkzElSu4u7LVWmUK',
      'created' => 1763821672,
      'currency' => 'eur',
      'fee_source' => 
      array (
        'charge' => 'py_1SWHn2PEpkzElSu4u7LVWmUK',
        'type' => 'charge',
      ),
      'livemode' => true,
      'originating_transaction' => 'ch_3SWHMYAns9lY52GQ1DMcPD6L',
      'refunded' => false,
      'refunds' => 
      array (
        'object' => 'list',
        'data' => 
        array (
        ),
        'has_more' => false,
        'total_count' => 0,
        'url' => '/v1/application_fees/fee_1SWHn2PEpkzElSu4aBTkXqDR/refunds',
      ),
    ),
    'created_at' => '2026-01-07 19:14:26',
    'updated_at' => '2026-01-07 20:47:44',
  ),
  175 => 
  array (
    'id' => 176,
    'stripe_fee_id' => 'fee_1SWHljPEpkzElSu4PdikFusj',
    'amount' => '4.16',
    'currency' => 'EUR',
    'created_at_stripe' => '2026-01-07 21:47:44',
    'stripe_account_id' => 'acct_1OrHzGPEpkzElSu4',
    'partner_email' => 'andreadellomodarme@gmail.com',
    'partner_name' => 'PUNTO P DI ANDREA DELL\'OMODARME',
    'client_id' => 383,
    'charge_id' => 'py_1SWHljPEpkzElSu4hlTceqxG',
    'description' => 'andreadellomodarme@gmail.com - acct_1OrHzGPEpkzElSu4',
    'period_month' => '2025-11',
    'raw_data' => 
    array (
      'id' => 'fee_1SWHljPEpkzElSu4PdikFusj',
      'object' => 'application_fee',
      'account' => 'acct_1OrHzGPEpkzElSu4',
      'amount' => 416,
      'amount_refunded' => 0,
      'application' => 'ca_Ox2Rdo90ojLoUICobfcdmeRJeF2Icbzs',
      'balance_transaction' => 'txn_1SWHlmAns9lY52GQEQoK65Hl',
      'charge' => 'py_1SWHljPEpkzElSu4hlTceqxG',
      'created' => 1763821591,
      'currency' => 'eur',
      'fee_source' => 
      array (
        'charge' => 'py_1SWHljPEpkzElSu4hlTceqxG',
        'type' => 'charge',
      ),
      'livemode' => true,
      'originating_transaction' => 'ch_3SWDZlAns9lY52GQ1GMs9Sf2',
      'refunded' => false,
      'refunds' => 
      array (
        'object' => 'list',
        'data' => 
        array (
        ),
        'has_more' => false,
        'total_count' => 0,
        'url' => '/v1/application_fees/fee_1SWHljPEpkzElSu4PdikFusj/refunds',
      ),
    ),
    'created_at' => '2026-01-07 19:14:27',
    'updated_at' => '2026-01-07 20:47:44',
  ),
  176 => 
  array (
    'id' => 177,
    'stripe_fee_id' => 'fee_1SWCVzPCrwFqsIfa4eOzNReB',
    'amount' => '2.83',
    'currency' => 'EUR',
    'created_at_stripe' => '2026-01-07 21:47:44',
    'stripe_account_id' => 'acct_1OhbRBPCrwFqsIfa',
    'partner_email' => 'maeva2000@inwind.it',
    'partner_name' => 'SPEEDY PIZZA DI MASSIMILIANO SILVESTRI',
    'client_id' => 358,
    'charge_id' => 'py_1SWCVyPCrwFqsIfaWooosTti',
    'description' => 'maeva2000@inwind.it - acct_1OhbRBPCrwFqsIfa',
    'period_month' => '2025-11',
    'raw_data' => 
    array (
      'id' => 'fee_1SWCVzPCrwFqsIfa4eOzNReB',
      'object' => 'application_fee',
      'account' => 'acct_1OhbRBPCrwFqsIfa',
      'amount' => 283,
      'amount_refunded' => 0,
      'application' => 'ca_Ox2Rdo90ojLoUICobfcdmeRJeF2Icbzs',
      'balance_transaction' => 'txn_1SWCW1Ans9lY52GQCpIDaynX',
      'charge' => 'py_1SWCVyPCrwFqsIfaWooosTti',
      'created' => 1763801395,
      'currency' => 'eur',
      'fee_source' => 
      array (
        'charge' => 'py_1SWCVyPCrwFqsIfaWooosTti',
        'type' => 'charge',
      ),
      'livemode' => true,
      'originating_transaction' => 'ch_3SWCTRAns9lY52GQ12f7kufq',
      'refunded' => false,
      'refunds' => 
      array (
        'object' => 'list',
        'data' => 
        array (
        ),
        'has_more' => false,
        'total_count' => 0,
        'url' => '/v1/application_fees/fee_1SWCVzPCrwFqsIfa4eOzNReB/refunds',
      ),
    ),
    'created_at' => '2026-01-07 19:14:27',
    'updated_at' => '2026-01-07 20:47:44',
  ),
  177 => 
  array (
    'id' => 178,
    'stripe_fee_id' => 'fee_1SVzclPCrwFqsIfaCCiIPfVL',
    'amount' => '3.00',
    'currency' => 'EUR',
    'created_at_stripe' => '2026-01-07 21:47:44',
    'stripe_account_id' => 'acct_1OhbRBPCrwFqsIfa',
    'partner_email' => 'maeva2000@inwind.it',
    'partner_name' => 'SPEEDY PIZZA DI MASSIMILIANO SILVESTRI',
    'client_id' => 358,
    'charge_id' => 'py_1SVzckPCrwFqsIfatApYQb4I',
    'description' => 'maeva2000@inwind.it - acct_1OhbRBPCrwFqsIfa',
    'period_month' => '2025-11',
    'raw_data' => 
    array (
      'id' => 'fee_1SVzclPCrwFqsIfaCCiIPfVL',
      'object' => 'application_fee',
      'account' => 'acct_1OhbRBPCrwFqsIfa',
      'amount' => 300,
      'amount_refunded' => 0,
      'application' => 'ca_Ox2Rdo90ojLoUICobfcdmeRJeF2Icbzs',
      'balance_transaction' => 'txn_1SVzcnAns9lY52GQYRmcXIYP',
      'charge' => 'py_1SVzckPCrwFqsIfatApYQb4I',
      'created' => 1763751843,
      'currency' => 'eur',
      'fee_source' => 
      array (
        'charge' => 'py_1SVzckPCrwFqsIfatApYQb4I',
        'type' => 'charge',
      ),
      'livemode' => true,
      'originating_transaction' => 'ch_3SVzc8Ans9lY52GQ1YKUT1iW',
      'refunded' => false,
      'refunds' => 
      array (
        'object' => 'list',
        'data' => 
        array (
        ),
        'has_more' => false,
        'total_count' => 0,
        'url' => '/v1/application_fees/fee_1SVzclPCrwFqsIfaCCiIPfVL/refunds',
      ),
    ),
    'created_at' => '2026-01-07 19:14:28',
    'updated_at' => '2026-01-07 20:47:44',
  ),
  178 => 
  array (
    'id' => 179,
    'stripe_fee_id' => 'fee_1SVzarAcaExTZKe8PuWvGnqS',
    'amount' => '4.20',
    'currency' => 'EUR',
    'created_at_stripe' => '2026-01-07 21:47:44',
    'stripe_account_id' => 'acct_1QsMHWAcaExTZKe8',
    'partner_email' => 'nclcucc@gmail.com',
    'partner_name' => 'La Loggia sul Mare SRL',
    'client_id' => 322,
    'charge_id' => 'py_1SVzarAcaExTZKe8E1aNg1Rh',
    'description' => 'nclcucc@gmail.com - acct_1QsMHWAcaExTZKe8',
    'period_month' => '2025-11',
    'raw_data' => 
    array (
      'id' => 'fee_1SVzarAcaExTZKe8PuWvGnqS',
      'object' => 'application_fee',
      'account' => 'acct_1QsMHWAcaExTZKe8',
      'amount' => 420,
      'amount_refunded' => 0,
      'application' => 'ca_Ox2Rdo90ojLoUICobfcdmeRJeF2Icbzs',
      'balance_transaction' => 'txn_1SVzatAns9lY52GQ5EVVEfAZ',
      'charge' => 'py_1SVzarAcaExTZKe8E1aNg1Rh',
      'created' => 1763751725,
      'currency' => 'eur',
      'fee_source' => 
      array (
        'charge' => 'py_1SVzarAcaExTZKe8E1aNg1Rh',
        'type' => 'charge',
      ),
      'livemode' => true,
      'originating_transaction' => 'ch_3SVzQDAns9lY52GQ1I3yGsIr',
      'refunded' => false,
      'refunds' => 
      array (
        'object' => 'list',
        'data' => 
        array (
        ),
        'has_more' => false,
        'total_count' => 0,
        'url' => '/v1/application_fees/fee_1SVzarAcaExTZKe8PuWvGnqS/refunds',
      ),
    ),
    'created_at' => '2026-01-07 19:14:29',
    'updated_at' => '2026-01-07 20:47:44',
  ),
  179 => 
  array (
    'id' => 180,
    'stripe_fee_id' => 'fee_1SVyTTPCrwFqsIfaUDQKrhtk',
    'amount' => '3.11',
    'currency' => 'EUR',
    'created_at_stripe' => '2026-01-07 21:47:44',
    'stripe_account_id' => 'acct_1OhbRBPCrwFqsIfa',
    'partner_email' => 'maeva2000@inwind.it',
    'partner_name' => 'SPEEDY PIZZA DI MASSIMILIANO SILVESTRI',
    'client_id' => 358,
    'charge_id' => 'py_1SVyTTPCrwFqsIfaSrts40f5',
    'description' => 'maeva2000@inwind.it - acct_1OhbRBPCrwFqsIfa',
    'period_month' => '2025-11',
    'raw_data' => 
    array (
      'id' => 'fee_1SVyTTPCrwFqsIfaUDQKrhtk',
      'object' => 'application_fee',
      'account' => 'acct_1OhbRBPCrwFqsIfa',
      'amount' => 311,
      'amount_refunded' => 0,
      'application' => 'ca_Ox2Rdo90ojLoUICobfcdmeRJeF2Icbzs',
      'balance_transaction' => 'txn_1SVyTWAns9lY52GQbhndeNTa',
      'charge' => 'py_1SVyTTPCrwFqsIfaSrts40f5',
      'created' => 1763747423,
      'currency' => 'eur',
      'fee_source' => 
      array (
        'charge' => 'py_1SVyTTPCrwFqsIfaSrts40f5',
        'type' => 'charge',
      ),
      'livemode' => true,
      'originating_transaction' => 'ch_3SVyFYAns9lY52GQ0eKpowF0',
      'refunded' => false,
      'refunds' => 
      array (
        'object' => 'list',
        'data' => 
        array (
        ),
        'has_more' => false,
        'total_count' => 0,
        'url' => '/v1/application_fees/fee_1SVyTTPCrwFqsIfaUDQKrhtk/refunds',
      ),
    ),
    'created_at' => '2026-01-07 19:14:30',
    'updated_at' => '2026-01-07 20:47:44',
  ),
  180 => 
  array (
    'id' => 181,
    'stripe_fee_id' => 'fee_1SVyCkPIzlXORG3aWEr3qCSs',
    'amount' => '5.95',
    'currency' => 'EUR',
    'created_at_stripe' => '2026-01-07 21:47:44',
    'stripe_account_id' => 'acct_1OYpemPIzlXORG3a',
    'partner_email' => 'feusrl.2019@gmail.com',
    'partner_name' => 'Feu Srl',
    'client_id' => 301,
    'charge_id' => 'py_1SVyCkPIzlXORG3ajynfyiVU',
    'description' => 'feusrl.2019@gmail.com - acct_1OYpemPIzlXORG3a',
    'period_month' => '2025-11',
    'raw_data' => 
    array (
      'id' => 'fee_1SVyCkPIzlXORG3aWEr3qCSs',
      'object' => 'application_fee',
      'account' => 'acct_1OYpemPIzlXORG3a',
      'amount' => 595,
      'amount_refunded' => 0,
      'application' => 'ca_Ox2Rdo90ojLoUICobfcdmeRJeF2Icbzs',
      'balance_transaction' => 'txn_1SVyCmAns9lY52GQDGqHfpQO',
      'charge' => 'py_1SVyCkPIzlXORG3ajynfyiVU',
      'created' => 1763746386,
      'currency' => 'eur',
      'fee_source' => 
      array (
        'charge' => 'py_1SVyCkPIzlXORG3ajynfyiVU',
        'type' => 'charge',
      ),
      'livemode' => true,
      'originating_transaction' => 'ch_3SVwxKAns9lY52GQ1p27i2LX',
      'refunded' => false,
      'refunds' => 
      array (
        'object' => 'list',
        'data' => 
        array (
        ),
        'has_more' => false,
        'total_count' => 0,
        'url' => '/v1/application_fees/fee_1SVyCkPIzlXORG3aWEr3qCSs/refunds',
      ),
    ),
    'created_at' => '2026-01-07 19:14:31',
    'updated_at' => '2026-01-07 20:47:44',
  ),
  181 => 
  array (
    'id' => 182,
    'stripe_fee_id' => 'fee_1SVyCLPIzlXORG3axExtrrOu',
    'amount' => '5.10',
    'currency' => 'EUR',
    'created_at_stripe' => '2026-01-07 21:47:44',
    'stripe_account_id' => 'acct_1OYpemPIzlXORG3a',
    'partner_email' => 'feusrl.2019@gmail.com',
    'partner_name' => 'Feu Srl',
    'client_id' => 301,
    'charge_id' => 'py_1SVyCKPIzlXORG3aQsH5GjyK',
    'description' => 'feusrl.2019@gmail.com - acct_1OYpemPIzlXORG3a',
    'period_month' => '2025-11',
    'raw_data' => 
    array (
      'id' => 'fee_1SVyCLPIzlXORG3axExtrrOu',
      'object' => 'application_fee',
      'account' => 'acct_1OYpemPIzlXORG3a',
      'amount' => 510,
      'amount_refunded' => 0,
      'application' => 'ca_Ox2Rdo90ojLoUICobfcdmeRJeF2Icbzs',
      'balance_transaction' => 'txn_1SVyCNAns9lY52GQT1m4bsOl',
      'charge' => 'py_1SVyCKPIzlXORG3aQsH5GjyK',
      'created' => 1763746361,
      'currency' => 'eur',
      'fee_source' => 
      array (
        'charge' => 'py_1SVyCKPIzlXORG3aQsH5GjyK',
        'type' => 'charge',
      ),
      'livemode' => true,
      'originating_transaction' => 'ch_3SVxKwAns9lY52GQ1Qrk7BMo',
      'refunded' => false,
      'refunds' => 
      array (
        'object' => 'list',
        'data' => 
        array (
        ),
        'has_more' => false,
        'total_count' => 0,
        'url' => '/v1/application_fees/fee_1SVyCLPIzlXORG3axExtrrOu/refunds',
      ),
    ),
    'created_at' => '2026-01-07 19:14:31',
    'updated_at' => '2026-01-07 20:47:44',
  ),
  182 => 
  array (
    'id' => 183,
    'stripe_fee_id' => 'fee_1SVwlHPEpkzElSu4tSvWgAo3',
    'amount' => '3.73',
    'currency' => 'EUR',
    'created_at_stripe' => '2026-01-07 21:47:44',
    'stripe_account_id' => 'acct_1OrHzGPEpkzElSu4',
    'partner_email' => 'andreadellomodarme@gmail.com',
    'partner_name' => 'PUNTO P DI ANDREA DELL\'OMODARME',
    'client_id' => 383,
    'charge_id' => 'py_1SVwlHPEpkzElSu434i1ICdB',
    'description' => 'andreadellomodarme@gmail.com - acct_1OrHzGPEpkzElSu4',
    'period_month' => '2025-11',
    'raw_data' => 
    array (
      'id' => 'fee_1SVwlHPEpkzElSu4tSvWgAo3',
      'object' => 'application_fee',
      'account' => 'acct_1OrHzGPEpkzElSu4',
      'amount' => 373,
      'amount_refunded' => 0,
      'application' => 'ca_Ox2Rdo90ojLoUICobfcdmeRJeF2Icbzs',
      'balance_transaction' => 'txn_1SVwlJAns9lY52GQXZM6DWcP',
      'charge' => 'py_1SVwlHPEpkzElSu434i1ICdB',
      'created' => 1763740839,
      'currency' => 'eur',
      'fee_source' => 
      array (
        'charge' => 'py_1SVwlHPEpkzElSu434i1ICdB',
        'type' => 'charge',
      ),
      'livemode' => true,
      'originating_transaction' => 'ch_3SVfz5Ans9lY52GQ1nnMUr1H',
      'refunded' => false,
      'refunds' => 
      array (
        'object' => 'list',
        'data' => 
        array (
        ),
        'has_more' => false,
        'total_count' => 0,
        'url' => '/v1/application_fees/fee_1SVwlHPEpkzElSu4tSvWgAo3/refunds',
      ),
    ),
    'created_at' => '2026-01-07 19:14:32',
    'updated_at' => '2026-01-07 20:47:44',
  ),
  183 => 
  array (
    'id' => 184,
    'stripe_fee_id' => 'fee_1SVwhvPEpkzElSu46AJ2mvg3',
    'amount' => '2.94',
    'currency' => 'EUR',
    'created_at_stripe' => '2026-01-07 21:47:44',
    'stripe_account_id' => 'acct_1OrHzGPEpkzElSu4',
    'partner_email' => 'andreadellomodarme@gmail.com',
    'partner_name' => 'PUNTO P DI ANDREA DELL\'OMODARME',
    'client_id' => 383,
    'charge_id' => 'py_1SVwhuPEpkzElSu4Xd0HB0mM',
    'description' => 'andreadellomodarme@gmail.com - acct_1OrHzGPEpkzElSu4',
    'period_month' => '2025-11',
    'raw_data' => 
    array (
      'id' => 'fee_1SVwhvPEpkzElSu46AJ2mvg3',
      'object' => 'application_fee',
      'account' => 'acct_1OrHzGPEpkzElSu4',
      'amount' => 294,
      'amount_refunded' => 0,
      'application' => 'ca_Ox2Rdo90ojLoUICobfcdmeRJeF2Icbzs',
      'balance_transaction' => 'txn_1SVwhxAns9lY52GQS90BXkr4',
      'charge' => 'py_1SVwhuPEpkzElSu4Xd0HB0mM',
      'created' => 1763740631,
      'currency' => 'eur',
      'fee_source' => 
      array (
        'charge' => 'py_1SVwhuPEpkzElSu4Xd0HB0mM',
        'type' => 'charge',
      ),
      'livemode' => true,
      'originating_transaction' => 'ch_3SVwR2Ans9lY52GQ0UfQJIKe',
      'refunded' => false,
      'refunds' => 
      array (
        'object' => 'list',
        'data' => 
        array (
        ),
        'has_more' => false,
        'total_count' => 0,
        'url' => '/v1/application_fees/fee_1SVwhvPEpkzElSu46AJ2mvg3/refunds',
      ),
    ),
    'created_at' => '2026-01-07 19:14:33',
    'updated_at' => '2026-01-07 20:47:44',
  ),
  184 => 
  array (
    'id' => 185,
    'stripe_fee_id' => 'fee_1SVdkwPFSTNU0nUGTSpI5KC3',
    'amount' => '3.07',
    'currency' => 'EUR',
    'created_at_stripe' => '2026-01-07 21:47:44',
    'stripe_account_id' => 'acct_1OtvsmPFSTNU0nUG',
    'partner_email' => 'fortipizza@gmail.com',
    'partner_name' => 'Forti Pizza e Torta',
    'client_id' => 303,
    'charge_id' => 'py_1SVdkwPFSTNU0nUGIVIiiyr6',
    'description' => 'fortipizza@gmail.com - acct_1OtvsmPFSTNU0nUG',
    'period_month' => '2025-11',
    'raw_data' => 
    array (
      'id' => 'fee_1SVdkwPFSTNU0nUGTSpI5KC3',
      'object' => 'application_fee',
      'account' => 'acct_1OtvsmPFSTNU0nUG',
      'amount' => 307,
      'amount_refunded' => 0,
      'application' => 'ca_Ox2Rdo90ojLoUICobfcdmeRJeF2Icbzs',
      'balance_transaction' => 'txn_1SVdkyAns9lY52GQxUvQmF9P',
      'charge' => 'py_1SVdkwPFSTNU0nUGIVIiiyr6',
      'created' => 1763667782,
      'currency' => 'eur',
      'fee_source' => 
      array (
        'charge' => 'py_1SVdkwPFSTNU0nUGIVIiiyr6',
        'type' => 'charge',
      ),
      'livemode' => true,
      'originating_transaction' => 'ch_3SVdjYAns9lY52GQ1QALl3hL',
      'refunded' => false,
      'refunds' => 
      array (
        'object' => 'list',
        'data' => 
        array (
        ),
        'has_more' => false,
        'total_count' => 0,
        'url' => '/v1/application_fees/fee_1SVdkwPFSTNU0nUGTSpI5KC3/refunds',
      ),
    ),
    'created_at' => '2026-01-07 19:14:34',
    'updated_at' => '2026-01-07 20:47:44',
  ),
  185 => 
  array (
    'id' => 186,
    'stripe_fee_id' => 'fee_1SVdWBPMwn5cW3ruFXfyBZhZ',
    'amount' => '2.91',
    'currency' => 'EUR',
    'created_at_stripe' => '2026-01-07 21:47:44',
    'stripe_account_id' => 'acct_1Pr0dOPMwn5cW3ru',
    'partner_email' => 'cavialegiallosrl@gmail.com',
    'partner_name' => 'Caviale Giallo SRL',
    'client_id' => 290,
    'charge_id' => 'py_1SVdWBPMwn5cW3ru20gTWKEb',
    'description' => 'cavialegiallosrl@gmail.com - acct_1Pr0dOPMwn5cW3ru',
    'period_month' => '2025-11',
    'raw_data' => 
    array (
      'id' => 'fee_1SVdWBPMwn5cW3ruFXfyBZhZ',
      'object' => 'application_fee',
      'account' => 'acct_1Pr0dOPMwn5cW3ru',
      'amount' => 291,
      'amount_refunded' => 0,
      'application' => 'ca_Ox2Rdo90ojLoUICobfcdmeRJeF2Icbzs',
      'balance_transaction' => 'txn_1SVdWDAns9lY52GQPEMtjqhj',
      'charge' => 'py_1SVdWBPMwn5cW3ru20gTWKEb',
      'created' => 1763666867,
      'currency' => 'eur',
      'fee_source' => 
      array (
        'charge' => 'py_1SVdWBPMwn5cW3ru20gTWKEb',
        'type' => 'charge',
      ),
      'livemode' => true,
      'originating_transaction' => 'ch_3SVdOeAns9lY52GQ14BElJ45',
      'refunded' => false,
      'refunds' => 
      array (
        'object' => 'list',
        'data' => 
        array (
        ),
        'has_more' => false,
        'total_count' => 0,
        'url' => '/v1/application_fees/fee_1SVdWBPMwn5cW3ruFXfyBZhZ/refunds',
      ),
    ),
    'created_at' => '2026-01-07 19:14:34',
    'updated_at' => '2026-01-07 20:47:44',
  ),
  186 => 
  array (
    'id' => 187,
    'stripe_fee_id' => 'fee_1SVcYmPCrwFqsIfaiJKvlsBm',
    'amount' => '2.82',
    'currency' => 'EUR',
    'created_at_stripe' => '2026-01-07 21:47:44',
    'stripe_account_id' => 'acct_1OhbRBPCrwFqsIfa',
    'partner_email' => 'maeva2000@inwind.it',
    'partner_name' => 'SPEEDY PIZZA DI MASSIMILIANO SILVESTRI',
    'client_id' => 358,
    'charge_id' => 'py_1SVcYlPCrwFqsIfaqQ1sT2mi',
    'description' => 'maeva2000@inwind.it - acct_1OhbRBPCrwFqsIfa',
    'period_month' => '2025-11',
    'raw_data' => 
    array (
      'id' => 'fee_1SVcYmPCrwFqsIfaiJKvlsBm',
      'object' => 'application_fee',
      'account' => 'acct_1OhbRBPCrwFqsIfa',
      'amount' => 282,
      'amount_refunded' => 0,
      'application' => 'ca_Ox2Rdo90ojLoUICobfcdmeRJeF2Icbzs',
      'balance_transaction' => 'txn_1SVcYoAns9lY52GQBE951nQK',
      'charge' => 'py_1SVcYlPCrwFqsIfaqQ1sT2mi',
      'created' => 1763663184,
      'currency' => 'eur',
      'fee_source' => 
      array (
        'charge' => 'py_1SVcYlPCrwFqsIfaqQ1sT2mi',
        'type' => 'charge',
      ),
      'livemode' => true,
      'originating_transaction' => 'ch_3SVcXjAns9lY52GQ0VYaWuGU',
      'refunded' => false,
      'refunds' => 
      array (
        'object' => 'list',
        'data' => 
        array (
        ),
        'has_more' => false,
        'total_count' => 0,
        'url' => '/v1/application_fees/fee_1SVcYmPCrwFqsIfaiJKvlsBm/refunds',
      ),
    ),
    'created_at' => '2026-01-07 19:14:35',
    'updated_at' => '2026-01-07 20:47:44',
  ),
  187 => 
  array (
    'id' => 188,
    'stripe_fee_id' => 'fee_1SVcISPIzlXORG3ayOd5GgCi',
    'amount' => '5.31',
    'currency' => 'EUR',
    'created_at_stripe' => '2026-01-07 21:47:44',
    'stripe_account_id' => 'acct_1OYpemPIzlXORG3a',
    'partner_email' => 'feusrl.2019@gmail.com',
    'partner_name' => 'Feu Srl',
    'client_id' => 301,
    'charge_id' => 'py_1SVcISPIzlXORG3a4BAN0klK',
    'description' => 'feusrl.2019@gmail.com - acct_1OYpemPIzlXORG3a',
    'period_month' => '2025-11',
    'raw_data' => 
    array (
      'id' => 'fee_1SVcISPIzlXORG3ayOd5GgCi',
      'object' => 'application_fee',
      'account' => 'acct_1OYpemPIzlXORG3a',
      'amount' => 531,
      'amount_refunded' => 0,
      'application' => 'ca_Ox2Rdo90ojLoUICobfcdmeRJeF2Icbzs',
      'balance_transaction' => 'txn_1SVcIUAns9lY52GQpUqdOlKB',
      'charge' => 'py_1SVcISPIzlXORG3a4BAN0klK',
      'created' => 1763662172,
      'currency' => 'eur',
      'fee_source' => 
      array (
        'charge' => 'py_1SVcISPIzlXORG3a4BAN0klK',
        'type' => 'charge',
      ),
      'livemode' => true,
      'originating_transaction' => 'ch_3SVb4tAns9lY52GQ0mVkHCT8',
      'refunded' => false,
      'refunds' => 
      array (
        'object' => 'list',
        'data' => 
        array (
        ),
        'has_more' => false,
        'total_count' => 0,
        'url' => '/v1/application_fees/fee_1SVcISPIzlXORG3ayOd5GgCi/refunds',
      ),
    ),
    'created_at' => '2026-01-07 19:14:36',
    'updated_at' => '2026-01-07 20:47:44',
  ),
  188 => 
  array (
    'id' => 189,
    'stripe_fee_id' => 'fee_1SVcDnAcaExTZKe8rhsaboWo',
    'amount' => '4.08',
    'currency' => 'EUR',
    'created_at_stripe' => '2026-01-07 21:47:44',
    'stripe_account_id' => 'acct_1QsMHWAcaExTZKe8',
    'partner_email' => 'nclcucc@gmail.com',
    'partner_name' => 'La Loggia sul Mare SRL',
    'client_id' => 322,
    'charge_id' => 'py_1SVcDnAcaExTZKe8UEFeF2DU',
    'description' => 'nclcucc@gmail.com - acct_1QsMHWAcaExTZKe8',
    'period_month' => '2025-11',
    'raw_data' => 
    array (
      'id' => 'fee_1SVcDnAcaExTZKe8rhsaboWo',
      'object' => 'application_fee',
      'account' => 'acct_1QsMHWAcaExTZKe8',
      'amount' => 408,
      'amount_refunded' => 0,
      'application' => 'ca_Ox2Rdo90ojLoUICobfcdmeRJeF2Icbzs',
      'balance_transaction' => 'txn_1SVcDqAns9lY52GQ8eOBS5xX',
      'charge' => 'py_1SVcDnAcaExTZKe8UEFeF2DU',
      'created' => 1763661883,
      'currency' => 'eur',
      'fee_source' => 
      array (
        'charge' => 'py_1SVcDnAcaExTZKe8UEFeF2DU',
        'type' => 'charge',
      ),
      'livemode' => true,
      'originating_transaction' => 'ch_3SVbXHAns9lY52GQ0mOrlNsy',
      'refunded' => false,
      'refunds' => 
      array (
        'object' => 'list',
        'data' => 
        array (
        ),
        'has_more' => false,
        'total_count' => 0,
        'url' => '/v1/application_fees/fee_1SVcDnAcaExTZKe8rhsaboWo/refunds',
      ),
    ),
    'created_at' => '2026-01-07 19:14:37',
    'updated_at' => '2026-01-07 20:47:44',
  ),
  189 => 
  array (
    'id' => 190,
    'stripe_fee_id' => 'fee_1SVbWqPAESt8veHwSt7ho4qd',
    'amount' => '2.89',
    'currency' => 'EUR',
    'created_at_stripe' => '2026-01-07 21:47:44',
    'stripe_account_id' => 'acct_1ROCdFPAESt8veHw',
    'partner_email' => 'anticatradizione1950@gmail.com',
    'partner_name' => 'Osteria Antica Tradizione srls.',
    'client_id' => 332,
    'charge_id' => 'py_1SVbWqPAESt8veHwKYI3lQ2n',
    'description' => 'anticatradizione1950@gmail.com - acct_1ROCdFPAESt8veHw',
    'period_month' => '2025-11',
    'raw_data' => 
    array (
      'id' => 'fee_1SVbWqPAESt8veHwSt7ho4qd',
      'object' => 'application_fee',
      'account' => 'acct_1ROCdFPAESt8veHw',
      'amount' => 289,
      'amount_refunded' => 0,
      'application' => 'ca_Ox2Rdo90ojLoUICobfcdmeRJeF2Icbzs',
      'balance_transaction' => 'txn_1SVbWsAns9lY52GQ1UqIt2VV',
      'charge' => 'py_1SVbWqPAESt8veHwKYI3lQ2n',
      'created' => 1763659220,
      'currency' => 'eur',
      'fee_source' => 
      array (
        'charge' => 'py_1SVbWqPAESt8veHwKYI3lQ2n',
        'type' => 'charge',
      ),
      'livemode' => true,
      'originating_transaction' => 'ch_3SVbVyAns9lY52GQ00d8Dflr',
      'refunded' => false,
      'refunds' => 
      array (
        'object' => 'list',
        'data' => 
        array (
        ),
        'has_more' => false,
        'total_count' => 0,
        'url' => '/v1/application_fees/fee_1SVbWqPAESt8veHwSt7ho4qd/refunds',
      ),
    ),
    'created_at' => '2026-01-07 19:14:37',
    'updated_at' => '2026-01-07 20:47:44',
  ),
  190 => 
  array (
    'id' => 191,
    'stripe_fee_id' => 'fee_1SVbHuPFSTNU0nUGHTVvURL3',
    'amount' => '3.03',
    'currency' => 'EUR',
    'created_at_stripe' => '2026-01-07 21:47:44',
    'stripe_account_id' => 'acct_1OtvsmPFSTNU0nUG',
    'partner_email' => 'fortipizza@gmail.com',
    'partner_name' => 'Forti Pizza e Torta',
    'client_id' => 303,
    'charge_id' => 'py_1SVbHuPFSTNU0nUGqt4thmvo',
    'description' => 'fortipizza@gmail.com - acct_1OtvsmPFSTNU0nUG',
    'period_month' => '2025-11',
    'raw_data' => 
    array (
      'id' => 'fee_1SVbHuPFSTNU0nUGHTVvURL3',
      'object' => 'application_fee',
      'account' => 'acct_1OtvsmPFSTNU0nUG',
      'amount' => 303,
      'amount_refunded' => 0,
      'application' => 'ca_Ox2Rdo90ojLoUICobfcdmeRJeF2Icbzs',
      'balance_transaction' => 'txn_1SVbHwAns9lY52GQMkNXDuSI',
      'charge' => 'py_1SVbHuPFSTNU0nUGqt4thmvo',
      'created' => 1763658294,
      'currency' => 'eur',
      'fee_source' => 
      array (
        'charge' => 'py_1SVbHuPFSTNU0nUGqt4thmvo',
        'type' => 'charge',
      ),
      'livemode' => true,
      'originating_transaction' => 'ch_3SVb9hAns9lY52GQ0pCmRxMM',
      'refunded' => false,
      'refunds' => 
      array (
        'object' => 'list',
        'data' => 
        array (
        ),
        'has_more' => false,
        'total_count' => 0,
        'url' => '/v1/application_fees/fee_1SVbHuPFSTNU0nUGHTVvURL3/refunds',
      ),
    ),
    'created_at' => '2026-01-07 19:14:38',
    'updated_at' => '2026-01-07 20:47:44',
  ),
  191 => 
  array (
    'id' => 192,
    'stripe_fee_id' => 'fee_1SVajWPB8fwsTso3Lcw2Ywyp',
    'amount' => '2.73',
    'currency' => 'EUR',
    'created_at_stripe' => '2026-01-07 21:47:44',
    'stripe_account_id' => 'acct_1Oe1NCPB8fwsTso3',
    'partner_email' => 'alepizza2000@gmail.com',
    'partner_name' => 'PIZZA E TORTA DA ALEPIZZA DI BIANCHI ALESSIO',
    'client_id' => 336,
    'charge_id' => 'py_1SVajWPB8fwsTso3cckUjtcw',
    'description' => 'alepizza2000@gmail.com - acct_1Oe1NCPB8fwsTso3',
    'period_month' => '2025-11',
    'raw_data' => 
    array (
      'id' => 'fee_1SVajWPB8fwsTso3Lcw2Ywyp',
      'object' => 'application_fee',
      'account' => 'acct_1Oe1NCPB8fwsTso3',
      'amount' => 273,
      'amount_refunded' => 0,
      'application' => 'ca_Ox2Rdo90ojLoUICobfcdmeRJeF2Icbzs',
      'balance_transaction' => 'txn_1SVajZAns9lY52GQCxsximuf',
      'charge' => 'py_1SVajWPB8fwsTso3cckUjtcw',
      'created' => 1763656162,
      'currency' => 'eur',
      'fee_source' => 
      array (
        'charge' => 'py_1SVajWPB8fwsTso3cckUjtcw',
        'type' => 'charge',
      ),
      'livemode' => true,
      'originating_transaction' => 'ch_3SVY3dAns9lY52GQ0r7q4MCK',
      'refunded' => false,
      'refunds' => 
      array (
        'object' => 'list',
        'data' => 
        array (
        ),
        'has_more' => false,
        'total_count' => 0,
        'url' => '/v1/application_fees/fee_1SVajWPB8fwsTso3Lcw2Ywyp/refunds',
      ),
    ),
    'created_at' => '2026-01-07 19:14:39',
    'updated_at' => '2026-01-07 20:47:44',
  ),
  192 => 
  array (
    'id' => 193,
    'stripe_fee_id' => 'fee_1SVVxjPCrwFqsIfaHcKjQ2c3',
    'amount' => '2.94',
    'currency' => 'EUR',
    'created_at_stripe' => '2026-01-07 21:47:44',
    'stripe_account_id' => 'acct_1OhbRBPCrwFqsIfa',
    'partner_email' => 'maeva2000@inwind.it',
    'partner_name' => 'SPEEDY PIZZA DI MASSIMILIANO SILVESTRI',
    'client_id' => 358,
    'charge_id' => 'py_1SVVxjPCrwFqsIfa45EqumCV',
    'description' => 'maeva2000@inwind.it - acct_1OhbRBPCrwFqsIfa',
    'period_month' => '2025-11',
    'raw_data' => 
    array (
      'id' => 'fee_1SVVxjPCrwFqsIfaHcKjQ2c3',
      'object' => 'application_fee',
      'account' => 'acct_1OhbRBPCrwFqsIfa',
      'amount' => 294,
      'amount_refunded' => 0,
      'application' => 'ca_Ox2Rdo90ojLoUICobfcdmeRJeF2Icbzs',
      'balance_transaction' => 'txn_1SVVxlAns9lY52GQTzMpSlLQ',
      'charge' => 'py_1SVVxjPCrwFqsIfa45EqumCV',
      'created' => 1763637823,
      'currency' => 'eur',
      'fee_source' => 
      array (
        'charge' => 'py_1SVVxjPCrwFqsIfa45EqumCV',
        'type' => 'charge',
      ),
      'livemode' => true,
      'originating_transaction' => 'ch_3SVVuvAns9lY52GQ1IgwVxyB',
      'refunded' => false,
      'refunds' => 
      array (
        'object' => 'list',
        'data' => 
        array (
        ),
        'has_more' => false,
        'total_count' => 0,
        'url' => '/v1/application_fees/fee_1SVVxjPCrwFqsIfaHcKjQ2c3/refunds',
      ),
    ),
    'created_at' => '2026-01-07 19:14:40',
    'updated_at' => '2026-01-07 20:47:44',
  ),
  193 => 
  array (
    'id' => 194,
    'stripe_fee_id' => 'fee_1SVFI9PFSTNU0nUGQ6jNyT68',
    'amount' => '2.96',
    'currency' => 'EUR',
    'created_at_stripe' => '2026-01-07 21:47:44',
    'stripe_account_id' => 'acct_1OtvsmPFSTNU0nUG',
    'partner_email' => 'fortipizza@gmail.com',
    'partner_name' => 'Forti Pizza e Torta',
    'client_id' => 303,
    'charge_id' => 'py_1SVFI8PFSTNU0nUGDrsfx5vC',
    'description' => 'fortipizza@gmail.com - acct_1OtvsmPFSTNU0nUG',
    'period_month' => '2025-11',
    'raw_data' => 
    array (
      'id' => 'fee_1SVFI9PFSTNU0nUGQ6jNyT68',
      'object' => 'application_fee',
      'account' => 'acct_1OtvsmPFSTNU0nUG',
      'amount' => 296,
      'amount_refunded' => 0,
      'application' => 'ca_Ox2Rdo90ojLoUICobfcdmeRJeF2Icbzs',
      'balance_transaction' => 'txn_1SVFIBAns9lY52GQvQf1lRfU',
      'charge' => 'py_1SVFI8PFSTNU0nUGDrsfx5vC',
      'created' => 1763573741,
      'currency' => 'eur',
      'fee_source' => 
      array (
        'charge' => 'py_1SVFI8PFSTNU0nUGDrsfx5vC',
        'type' => 'charge',
      ),
      'livemode' => true,
      'originating_transaction' => 'ch_3SVFDLAns9lY52GQ1F33nbX0',
      'refunded' => false,
      'refunds' => 
      array (
        'object' => 'list',
        'data' => 
        array (
        ),
        'has_more' => false,
        'total_count' => 0,
        'url' => '/v1/application_fees/fee_1SVFI9PFSTNU0nUGQ6jNyT68/refunds',
      ),
    ),
    'created_at' => '2026-01-07 19:14:40',
    'updated_at' => '2026-01-07 20:47:44',
  ),
  194 => 
  array (
    'id' => 195,
    'stripe_fee_id' => 'fee_1SVERYPCrwFqsIfa3AHhZ2uq',
    'amount' => '3.26',
    'currency' => 'EUR',
    'created_at_stripe' => '2026-01-07 21:47:44',
    'stripe_account_id' => 'acct_1OhbRBPCrwFqsIfa',
    'partner_email' => 'maeva2000@inwind.it',
    'partner_name' => 'SPEEDY PIZZA DI MASSIMILIANO SILVESTRI',
    'client_id' => 358,
    'charge_id' => 'py_1SVERYPCrwFqsIfaa23wTg5O',
    'description' => 'maeva2000@inwind.it - acct_1OhbRBPCrwFqsIfa',
    'period_month' => '2025-11',
    'raw_data' => 
    array (
      'id' => 'fee_1SVERYPCrwFqsIfa3AHhZ2uq',
      'object' => 'application_fee',
      'account' => 'acct_1OhbRBPCrwFqsIfa',
      'amount' => 326,
      'amount_refunded' => 0,
      'application' => 'ca_Ox2Rdo90ojLoUICobfcdmeRJeF2Icbzs',
      'balance_transaction' => 'txn_1SVERbAns9lY52GQRIyKZsnc',
      'charge' => 'py_1SVERYPCrwFqsIfaa23wTg5O',
      'created' => 1763570480,
      'currency' => 'eur',
      'fee_source' => 
      array (
        'charge' => 'py_1SVERYPCrwFqsIfaa23wTg5O',
        'type' => 'charge',
      ),
      'livemode' => true,
      'originating_transaction' => 'ch_3SVEAKAns9lY52GQ0apYtxLr',
      'refunded' => false,
      'refunds' => 
      array (
        'object' => 'list',
        'data' => 
        array (
        ),
        'has_more' => false,
        'total_count' => 0,
        'url' => '/v1/application_fees/fee_1SVERYPCrwFqsIfa3AHhZ2uq/refunds',
      ),
    ),
    'created_at' => '2026-01-07 19:14:41',
    'updated_at' => '2026-01-07 20:47:44',
  ),
  195 => 
  array (
    'id' => 196,
    'stripe_fee_id' => 'fee_1SVCsMPEpkzElSu4pxvLWGrc',
    'amount' => '2.93',
    'currency' => 'EUR',
    'created_at_stripe' => '2026-01-07 21:47:44',
    'stripe_account_id' => 'acct_1OrHzGPEpkzElSu4',
    'partner_email' => 'andreadellomodarme@gmail.com',
    'partner_name' => 'PUNTO P DI ANDREA DELL\'OMODARME',
    'client_id' => 383,
    'charge_id' => 'py_1SVCsMPEpkzElSu4dD9jk3BC',
    'description' => 'andreadellomodarme@gmail.com - acct_1OrHzGPEpkzElSu4',
    'period_month' => '2025-11',
    'raw_data' => 
    array (
      'id' => 'fee_1SVCsMPEpkzElSu4pxvLWGrc',
      'object' => 'application_fee',
      'account' => 'acct_1OrHzGPEpkzElSu4',
      'amount' => 293,
      'amount_refunded' => 0,
      'application' => 'ca_Ox2Rdo90ojLoUICobfcdmeRJeF2Icbzs',
      'balance_transaction' => 'txn_1SVCsOAns9lY52GQ1EaVZu6v',
      'charge' => 'py_1SVCsMPEpkzElSu4dD9jk3BC',
      'created' => 1763564454,
      'currency' => 'eur',
      'fee_source' => 
      array (
        'charge' => 'py_1SVCsMPEpkzElSu4dD9jk3BC',
        'type' => 'charge',
      ),
      'livemode' => true,
      'originating_transaction' => 'ch_3SVCczAns9lY52GQ0HctGo3b',
      'refunded' => false,
      'refunds' => 
      array (
        'object' => 'list',
        'data' => 
        array (
        ),
        'has_more' => false,
        'total_count' => 0,
        'url' => '/v1/application_fees/fee_1SVCsMPEpkzElSu4pxvLWGrc/refunds',
      ),
    ),
    'created_at' => '2026-01-07 19:14:42',
    'updated_at' => '2026-01-07 20:47:44',
  ),
  196 => 
  array (
    'id' => 197,
    'stripe_fee_id' => 'fee_1SUWtAPCrwFqsIfadz5kKBLK',
    'amount' => '2.92',
    'currency' => 'EUR',
    'created_at_stripe' => '2026-01-07 21:47:44',
    'stripe_account_id' => 'acct_1OhbRBPCrwFqsIfa',
    'partner_email' => 'maeva2000@inwind.it',
    'partner_name' => 'SPEEDY PIZZA DI MASSIMILIANO SILVESTRI',
    'client_id' => 358,
    'charge_id' => 'py_1SUWtAPCrwFqsIfalTwElMWP',
    'description' => 'maeva2000@inwind.it - acct_1OhbRBPCrwFqsIfa',
    'period_month' => '2025-11',
    'raw_data' => 
    array (
      'id' => 'fee_1SUWtAPCrwFqsIfadz5kKBLK',
      'object' => 'application_fee',
      'account' => 'acct_1OhbRBPCrwFqsIfa',
      'amount' => 292,
      'amount_refunded' => 0,
      'application' => 'ca_Ox2Rdo90ojLoUICobfcdmeRJeF2Icbzs',
      'balance_transaction' => 'txn_1SUWtCAns9lY52GQz8XpnYqp',
      'charge' => 'py_1SUWtAPCrwFqsIfalTwElMWP',
      'created' => 1763403056,
      'currency' => 'eur',
      'fee_source' => 
      array (
        'charge' => 'py_1SUWtAPCrwFqsIfalTwElMWP',
        'type' => 'charge',
      ),
      'livemode' => true,
      'originating_transaction' => 'ch_3SUWn6Ans9lY52GQ06Ili0WU',
      'refunded' => false,
      'refunds' => 
      array (
        'object' => 'list',
        'data' => 
        array (
        ),
        'has_more' => false,
        'total_count' => 0,
        'url' => '/v1/application_fees/fee_1SUWtAPCrwFqsIfadz5kKBLK/refunds',
      ),
    ),
    'created_at' => '2026-01-07 19:14:43',
    'updated_at' => '2026-01-07 20:47:44',
  ),
  197 => 
  array (
    'id' => 198,
    'stripe_fee_id' => 'fee_1SUV63ArGwCSIIveOWKbsRwv',
    'amount' => '3.79',
    'currency' => 'EUR',
    'created_at_stripe' => '2026-01-07 21:47:44',
    'stripe_account_id' => 'acct_1SRyIkArGwCSIIve',
    'partner_email' => 'ordinazioni@sbriciolopizza.it',
    'partner_name' => 'PACIFIC JAFFE S.R.L.',
    'client_id' => 333,
    'charge_id' => 'py_1SUV63ArGwCSIIve8xFYzFWB',
    'description' => 'ordinazioni@sbriciolopizza.it - acct_1SRyIkArGwCSIIve',
    'period_month' => '2025-11',
    'raw_data' => 
    array (
      'id' => 'fee_1SUV63ArGwCSIIveOWKbsRwv',
      'object' => 'application_fee',
      'account' => 'acct_1SRyIkArGwCSIIve',
      'amount' => 379,
      'amount_refunded' => 0,
      'application' => 'ca_Ox2Rdo90ojLoUICobfcdmeRJeF2Icbzs',
      'balance_transaction' => 'txn_1SUV65Ans9lY52GQjszCVyCK',
      'charge' => 'py_1SUV63ArGwCSIIve8xFYzFWB',
      'created' => 1763396167,
      'currency' => 'eur',
      'fee_source' => 
      array (
        'charge' => 'py_1SUV63ArGwCSIIve8xFYzFWB',
        'type' => 'charge',
      ),
      'livemode' => true,
      'originating_transaction' => 'ch_3SUUxCAns9lY52GQ0pGFKL2G',
      'refunded' => false,
      'refunds' => 
      array (
        'object' => 'list',
        'data' => 
        array (
        ),
        'has_more' => false,
        'total_count' => 0,
        'url' => '/v1/application_fees/fee_1SUV63ArGwCSIIveOWKbsRwv/refunds',
      ),
    ),
    'created_at' => '2026-01-07 19:14:44',
    'updated_at' => '2026-01-07 20:47:44',
  ),
  198 => 
  array (
    'id' => 199,
    'stripe_fee_id' => 'fee_1SUA1ePIzlXORG3aNcKBYrqz',
    'amount' => '4.93',
    'currency' => 'EUR',
    'created_at_stripe' => '2026-01-07 21:47:44',
    'stripe_account_id' => 'acct_1OYpemPIzlXORG3a',
    'partner_email' => 'feusrl.2019@gmail.com',
    'partner_name' => 'Feu Srl',
    'client_id' => 301,
    'charge_id' => 'py_1SUA1ePIzlXORG3ahPKlGvy9',
    'description' => 'feusrl.2019@gmail.com - acct_1OYpemPIzlXORG3a',
    'period_month' => '2025-11',
    'raw_data' => 
    array (
      'id' => 'fee_1SUA1ePIzlXORG3aNcKBYrqz',
      'object' => 'application_fee',
      'account' => 'acct_1OYpemPIzlXORG3a',
      'amount' => 493,
      'amount_refunded' => 0,
      'application' => 'ca_Ox2Rdo90ojLoUICobfcdmeRJeF2Icbzs',
      'balance_transaction' => 'txn_1SUA1hAns9lY52GQBBZYzcYf',
      'charge' => 'py_1SUA1ePIzlXORG3ahPKlGvy9',
      'created' => 1763315170,
      'currency' => 'eur',
      'fee_source' => 
      array (
        'charge' => 'py_1SUA1ePIzlXORG3ahPKlGvy9',
        'type' => 'charge',
      ),
      'livemode' => true,
      'originating_transaction' => 'ch_3SU9rwAns9lY52GQ1s9em13P',
      'refunded' => false,
      'refunds' => 
      array (
        'object' => 'list',
        'data' => 
        array (
        ),
        'has_more' => false,
        'total_count' => 0,
        'url' => '/v1/application_fees/fee_1SUA1ePIzlXORG3aNcKBYrqz/refunds',
      ),
    ),
    'created_at' => '2026-01-07 19:14:44',
    'updated_at' => '2026-01-07 20:47:44',
  ),
  199 => 
  array (
    'id' => 200,
    'stripe_fee_id' => 'fee_1STo9CPB7qjhlfVaavD91Kk6',
    'amount' => '2.77',
    'currency' => 'EUR',
    'created_at_stripe' => '2026-01-07 21:47:44',
    'stripe_account_id' => 'acct_1OrlPGPB7qjhlfVa',
    'partner_email' => 'laboratoriodellapizza@gmail.com',
    'partner_name' => 'Macrì Domenico',
    'client_id' => 325,
    'charge_id' => 'py_1STo9CPB7qjhlfVaKeT3DEei',
    'description' => 'laboratoriodellapizza@gmail.com - acct_1OrlPGPB7qjhlfVa',
    'period_month' => '2025-11',
    'raw_data' => 
    array (
      'id' => 'fee_1STo9CPB7qjhlfVaavD91Kk6',
      'object' => 'application_fee',
      'account' => 'acct_1OrlPGPB7qjhlfVa',
      'amount' => 277,
      'amount_refunded' => 0,
      'application' => 'ca_Ox2Rdo90ojLoUICobfcdmeRJeF2Icbzs',
      'balance_transaction' => 'txn_1STo9EAns9lY52GQZ5uVKZLl',
      'charge' => 'py_1STo9CPB7qjhlfVaKeT3DEei',
      'created' => 1763231070,
      'currency' => 'eur',
      'fee_source' => 
      array (
        'charge' => 'py_1STo9CPB7qjhlfVaKeT3DEei',
        'type' => 'charge',
      ),
      'livemode' => true,
      'originating_transaction' => 'ch_3STo8ZAns9lY52GQ1MuLAsuf',
      'refunded' => false,
      'refunds' => 
      array (
        'object' => 'list',
        'data' => 
        array (
        ),
        'has_more' => false,
        'total_count' => 0,
        'url' => '/v1/application_fees/fee_1STo9CPB7qjhlfVaavD91Kk6/refunds',
      ),
    ),
    'created_at' => '2026-01-07 19:14:45',
    'updated_at' => '2026-01-07 20:47:44',
  ),
  200 => 
  array (
    'id' => 201,
    'stripe_fee_id' => 'fee_1STo8MPB7qjhlfVaxJRKOTOW',
    'amount' => '2.97',
    'currency' => 'EUR',
    'created_at_stripe' => '2026-01-07 21:47:44',
    'stripe_account_id' => 'acct_1OrlPGPB7qjhlfVa',
    'partner_email' => 'laboratoriodellapizza@gmail.com',
    'partner_name' => 'Macrì Domenico',
    'client_id' => 325,
    'charge_id' => 'py_1STo8LPB7qjhlfVakKq3N2pD',
    'description' => 'laboratoriodellapizza@gmail.com - acct_1OrlPGPB7qjhlfVa',
    'period_month' => '2025-11',
    'raw_data' => 
    array (
      'id' => 'fee_1STo8MPB7qjhlfVaxJRKOTOW',
      'object' => 'application_fee',
      'account' => 'acct_1OrlPGPB7qjhlfVa',
      'amount' => 297,
      'amount_refunded' => 0,
      'application' => 'ca_Ox2Rdo90ojLoUICobfcdmeRJeF2Icbzs',
      'balance_transaction' => 'txn_1STo8OAns9lY52GQKPNFrlEt',
      'charge' => 'py_1STo8LPB7qjhlfVakKq3N2pD',
      'created' => 1763231018,
      'currency' => 'eur',
      'fee_source' => 
      array (
        'charge' => 'py_1STo8LPB7qjhlfVakKq3N2pD',
        'type' => 'charge',
      ),
      'livemode' => true,
      'originating_transaction' => 'ch_3STo7dAns9lY52GQ0coL2a5j',
      'refunded' => false,
      'refunds' => 
      array (
        'object' => 'list',
        'data' => 
        array (
        ),
        'has_more' => false,
        'total_count' => 0,
        'url' => '/v1/application_fees/fee_1STo8MPB7qjhlfVaxJRKOTOW/refunds',
      ),
    ),
    'created_at' => '2026-01-07 19:14:47',
    'updated_at' => '2026-01-07 20:47:44',
  ),
  201 => 
  array (
    'id' => 202,
    'stripe_fee_id' => 'fee_1STnIuPIzlXORG3aoZcXl0bw',
    'amount' => '5.11',
    'currency' => 'EUR',
    'created_at_stripe' => '2026-01-07 21:47:44',
    'stripe_account_id' => 'acct_1OYpemPIzlXORG3a',
    'partner_email' => 'feusrl.2019@gmail.com',
    'partner_name' => 'Feu Srl',
    'client_id' => 301,
    'charge_id' => 'py_1STnIuPIzlXORG3azzooElTH',
    'description' => 'feusrl.2019@gmail.com - acct_1OYpemPIzlXORG3a',
    'period_month' => '2025-11',
    'raw_data' => 
    array (
      'id' => 'fee_1STnIuPIzlXORG3aoZcXl0bw',
      'object' => 'application_fee',
      'account' => 'acct_1OYpemPIzlXORG3a',
      'amount' => 511,
      'amount_refunded' => 0,
      'application' => 'ca_Ox2Rdo90ojLoUICobfcdmeRJeF2Icbzs',
      'balance_transaction' => 'txn_1STnIxAns9lY52GQWBnI7uQn',
      'charge' => 'py_1STnIuPIzlXORG3azzooElTH',
      'created' => 1763227828,
      'currency' => 'eur',
      'fee_source' => 
      array (
        'charge' => 'py_1STnIuPIzlXORG3azzooElTH',
        'type' => 'charge',
      ),
      'livemode' => true,
      'originating_transaction' => 'ch_3STmeCAns9lY52GQ1RO7yat1',
      'refunded' => false,
      'refunds' => 
      array (
        'object' => 'list',
        'data' => 
        array (
        ),
        'has_more' => false,
        'total_count' => 0,
        'url' => '/v1/application_fees/fee_1STnIuPIzlXORG3aoZcXl0bw/refunds',
      ),
    ),
    'created_at' => '2026-01-07 19:14:47',
    'updated_at' => '2026-01-07 20:47:44',
  ),
  202 => 
  array (
    'id' => 203,
    'stripe_fee_id' => 'fee_1STlZJPEpkzElSu4mKnvrB5v',
    'amount' => '4.19',
    'currency' => 'EUR',
    'created_at_stripe' => '2026-01-07 21:47:44',
    'stripe_account_id' => 'acct_1OrHzGPEpkzElSu4',
    'partner_email' => 'andreadellomodarme@gmail.com',
    'partner_name' => 'PUNTO P DI ANDREA DELL\'OMODARME',
    'client_id' => 383,
    'charge_id' => 'py_1STlZJPEpkzElSu4A4mCYA6q',
    'description' => 'andreadellomodarme@gmail.com - acct_1OrHzGPEpkzElSu4',
    'period_month' => '2025-11',
    'raw_data' => 
    array (
      'id' => 'fee_1STlZJPEpkzElSu4mKnvrB5v',
      'object' => 'application_fee',
      'account' => 'acct_1OrHzGPEpkzElSu4',
      'amount' => 419,
      'amount_refunded' => 0,
      'application' => 'ca_Ox2Rdo90ojLoUICobfcdmeRJeF2Icbzs',
      'balance_transaction' => 'txn_1STlZLAns9lY52GQNscF09Gn',
      'charge' => 'py_1STlZJPEpkzElSu4A4mCYA6q',
      'created' => 1763221157,
      'currency' => 'eur',
      'fee_source' => 
      array (
        'charge' => 'py_1STlZJPEpkzElSu4A4mCYA6q',
        'type' => 'charge',
      ),
      'livemode' => true,
      'originating_transaction' => 'ch_3STlXNAns9lY52GQ1gBF2da4',
      'refunded' => false,
      'refunds' => 
      array (
        'object' => 'list',
        'data' => 
        array (
        ),
        'has_more' => false,
        'total_count' => 0,
        'url' => '/v1/application_fees/fee_1STlZJPEpkzElSu4mKnvrB5v/refunds',
      ),
    ),
    'created_at' => '2026-01-07 19:14:48',
    'updated_at' => '2026-01-07 20:47:44',
  ),
  203 => 
  array (
    'id' => 204,
    'stripe_fee_id' => 'fee_1STSXEPIzlXORG3awloGAu6K',
    'amount' => '5.37',
    'currency' => 'EUR',
    'created_at_stripe' => '2026-01-07 21:47:44',
    'stripe_account_id' => 'acct_1OYpemPIzlXORG3a',
    'partner_email' => 'feusrl.2019@gmail.com',
    'partner_name' => 'Feu Srl',
    'client_id' => 301,
    'charge_id' => 'py_1STSXDPIzlXORG3aq3forzva',
    'description' => 'feusrl.2019@gmail.com - acct_1OYpemPIzlXORG3a',
    'period_month' => '2025-11',
    'raw_data' => 
    array (
      'id' => 'fee_1STSXEPIzlXORG3awloGAu6K',
      'object' => 'application_fee',
      'account' => 'acct_1OYpemPIzlXORG3a',
      'amount' => 537,
      'amount_refunded' => 0,
      'application' => 'ca_Ox2Rdo90ojLoUICobfcdmeRJeF2Icbzs',
      'balance_transaction' => 'txn_1STSXGAns9lY52GQPPYlnLxW',
      'charge' => 'py_1STSXDPIzlXORG3aq3forzva',
      'created' => 1763147992,
      'currency' => 'eur',
      'fee_source' => 
      array (
        'charge' => 'py_1STSXDPIzlXORG3aq3forzva',
        'type' => 'charge',
      ),
      'livemode' => true,
      'originating_transaction' => 'ch_3STSTrAns9lY52GQ0AWzkLtp',
      'refunded' => false,
      'refunds' => 
      array (
        'object' => 'list',
        'data' => 
        array (
        ),
        'has_more' => false,
        'total_count' => 0,
        'url' => '/v1/application_fees/fee_1STSXEPIzlXORG3awloGAu6K/refunds',
      ),
    ),
    'created_at' => '2026-01-07 19:14:49',
    'updated_at' => '2026-01-07 20:47:44',
  ),
  204 => 
  array (
    'id' => 205,
    'stripe_fee_id' => 'fee_1STRY4PIzlXORG3a0b0GQhn5',
    'amount' => '5.12',
    'currency' => 'EUR',
    'created_at_stripe' => '2026-01-07 21:47:44',
    'stripe_account_id' => 'acct_1OYpemPIzlXORG3a',
    'partner_email' => 'feusrl.2019@gmail.com',
    'partner_name' => 'Feu Srl',
    'client_id' => 301,
    'charge_id' => 'py_1STRY3PIzlXORG3ayaXsUo6I',
    'description' => 'feusrl.2019@gmail.com - acct_1OYpemPIzlXORG3a',
    'period_month' => '2025-11',
    'raw_data' => 
    array (
      'id' => 'fee_1STRY4PIzlXORG3a0b0GQhn5',
      'object' => 'application_fee',
      'account' => 'acct_1OYpemPIzlXORG3a',
      'amount' => 512,
      'amount_refunded' => 0,
      'application' => 'ca_Ox2Rdo90ojLoUICobfcdmeRJeF2Icbzs',
      'balance_transaction' => 'txn_1STRY6Ans9lY52GQzUQ9pSFe',
      'charge' => 'py_1STRY3PIzlXORG3ayaXsUo6I',
      'created' => 1763144200,
      'currency' => 'eur',
      'fee_source' => 
      array (
        'charge' => 'py_1STRY3PIzlXORG3ayaXsUo6I',
        'type' => 'charge',
      ),
      'livemode' => true,
      'originating_transaction' => 'ch_3STRLzAns9lY52GQ1MJGxfRE',
      'refunded' => false,
      'refunds' => 
      array (
        'object' => 'list',
        'data' => 
        array (
        ),
        'has_more' => false,
        'total_count' => 0,
        'url' => '/v1/application_fees/fee_1STRY4PIzlXORG3a0b0GQhn5/refunds',
      ),
    ),
    'created_at' => '2026-01-07 19:14:49',
    'updated_at' => '2026-01-07 20:47:44',
  ),
  205 => 
  array (
    'id' => 206,
    'stripe_fee_id' => 'fee_1STPxsPEpkzElSu4mJQkGsYv',
    'amount' => '3.08',
    'currency' => 'EUR',
    'created_at_stripe' => '2026-01-07 21:47:44',
    'stripe_account_id' => 'acct_1OrHzGPEpkzElSu4',
    'partner_email' => 'andreadellomodarme@gmail.com',
    'partner_name' => 'PUNTO P DI ANDREA DELL\'OMODARME',
    'client_id' => 383,
    'charge_id' => 'py_1STPxsPEpkzElSu47KSzcolA',
    'description' => 'andreadellomodarme@gmail.com - acct_1OrHzGPEpkzElSu4',
    'period_month' => '2025-11',
    'raw_data' => 
    array (
      'id' => 'fee_1STPxsPEpkzElSu4mJQkGsYv',
      'object' => 'application_fee',
      'account' => 'acct_1OrHzGPEpkzElSu4',
      'amount' => 308,
      'amount_refunded' => 0,
      'application' => 'ca_Ox2Rdo90ojLoUICobfcdmeRJeF2Icbzs',
      'balance_transaction' => 'txn_1STPxvAns9lY52GQUKpvGo1e',
      'charge' => 'py_1STPxsPEpkzElSu47KSzcolA',
      'created' => 1763138112,
      'currency' => 'eur',
      'fee_source' => 
      array (
        'charge' => 'py_1STPxsPEpkzElSu47KSzcolA',
        'type' => 'charge',
      ),
      'livemode' => true,
      'originating_transaction' => 'ch_3STPlNAns9lY52GQ1h6mqxsu',
      'refunded' => false,
      'refunds' => 
      array (
        'object' => 'list',
        'data' => 
        array (
        ),
        'has_more' => false,
        'total_count' => 0,
        'url' => '/v1/application_fees/fee_1STPxsPEpkzElSu4mJQkGsYv/refunds',
      ),
    ),
    'created_at' => '2026-01-07 19:14:50',
    'updated_at' => '2026-01-07 20:47:44',
  ),
  206 => 
  array (
    'id' => 207,
    'stripe_fee_id' => 'fee_1STOlXPEpkzElSu4cPtA0RQ2',
    'amount' => '2.89',
    'currency' => 'EUR',
    'created_at_stripe' => '2026-01-07 21:47:44',
    'stripe_account_id' => 'acct_1OrHzGPEpkzElSu4',
    'partner_email' => 'andreadellomodarme@gmail.com',
    'partner_name' => 'PUNTO P DI ANDREA DELL\'OMODARME',
    'client_id' => 383,
    'charge_id' => 'py_1STOlXPEpkzElSu4oDMr9o8V',
    'description' => 'andreadellomodarme@gmail.com - acct_1OrHzGPEpkzElSu4',
    'period_month' => '2025-11',
    'raw_data' => 
    array (
      'id' => 'fee_1STOlXPEpkzElSu4cPtA0RQ2',
      'object' => 'application_fee',
      'account' => 'acct_1OrHzGPEpkzElSu4',
      'amount' => 289,
      'amount_refunded' => 0,
      'application' => 'ca_Ox2Rdo90ojLoUICobfcdmeRJeF2Icbzs',
      'balance_transaction' => 'txn_1STOlaAns9lY52GQO9jQsilI',
      'charge' => 'py_1STOlXPEpkzElSu4oDMr9o8V',
      'created' => 1763133503,
      'currency' => 'eur',
      'fee_source' => 
      array (
        'charge' => 'py_1STOlXPEpkzElSu4oDMr9o8V',
        'type' => 'charge',
      ),
      'livemode' => true,
      'originating_transaction' => 'ch_3STOcuAns9lY52GQ18HZElrh',
      'refunded' => false,
      'refunds' => 
      array (
        'object' => 'list',
        'data' => 
        array (
        ),
        'has_more' => false,
        'total_count' => 0,
        'url' => '/v1/application_fees/fee_1STOlXPEpkzElSu4cPtA0RQ2/refunds',
      ),
    ),
    'created_at' => '2026-01-07 19:14:51',
    'updated_at' => '2026-01-07 20:47:44',
  ),
  207 => 
  array (
    'id' => 208,
    'stripe_fee_id' => 'fee_1STKDdPIzlXORG3aw6q9ffoY',
    'amount' => '4.75',
    'currency' => 'EUR',
    'created_at_stripe' => '2026-01-07 21:47:44',
    'stripe_account_id' => 'acct_1OYpemPIzlXORG3a',
    'partner_email' => 'feusrl.2019@gmail.com',
    'partner_name' => 'Feu Srl',
    'client_id' => 301,
    'charge_id' => 'py_1STKDdPIzlXORG3a38siUTv7',
    'description' => 'feusrl.2019@gmail.com - acct_1OYpemPIzlXORG3a',
    'period_month' => '2025-11',
    'raw_data' => 
    array (
      'id' => 'fee_1STKDdPIzlXORG3aw6q9ffoY',
      'object' => 'application_fee',
      'account' => 'acct_1OYpemPIzlXORG3a',
      'amount' => 475,
      'amount_refunded' => 0,
      'application' => 'ca_Ox2Rdo90ojLoUICobfcdmeRJeF2Icbzs',
      'balance_transaction' => 'txn_1STKDgAns9lY52GQO1VypVVN',
      'charge' => 'py_1STKDdPIzlXORG3a38siUTv7',
      'created' => 1763116025,
      'currency' => 'eur',
      'fee_source' => 
      array (
        'charge' => 'py_1STKDdPIzlXORG3a38siUTv7',
        'type' => 'charge',
      ),
      'livemode' => true,
      'originating_transaction' => 'ch_3STJW1Ans9lY52GQ17AXfdap',
      'refunded' => false,
      'refunds' => 
      array (
        'object' => 'list',
        'data' => 
        array (
        ),
        'has_more' => false,
        'total_count' => 0,
        'url' => '/v1/application_fees/fee_1STKDdPIzlXORG3aw6q9ffoY/refunds',
      ),
    ),
    'created_at' => '2026-01-07 19:14:51',
    'updated_at' => '2026-01-07 20:47:44',
  ),
  208 => 
  array (
    'id' => 209,
    'stripe_fee_id' => 'fee_1ST6RiPBokpe0rfC5IKgYOVm',
    'amount' => '3.78',
    'currency' => 'EUR',
    'created_at_stripe' => '2026-01-07 21:47:44',
    'stripe_account_id' => 'acct_1RbPNaPBokpe0rfC',
    'partner_email' => 'aledilie87@gmail.com',
    'partner_name' => 'Pizzeria Ideale di Di Lieto Alessio',
    'client_id' => 341,
    'charge_id' => 'py_1ST6RiPBokpe0rfCmhj17cES',
    'description' => 'aledilie87@gmail.com - acct_1RbPNaPBokpe0rfC',
    'period_month' => '2025-11',
    'raw_data' => 
    array (
      'id' => 'fee_1ST6RiPBokpe0rfC5IKgYOVm',
      'object' => 'application_fee',
      'account' => 'acct_1RbPNaPBokpe0rfC',
      'amount' => 378,
      'amount_refunded' => 0,
      'application' => 'ca_Ox2Rdo90ojLoUICobfcdmeRJeF2Icbzs',
      'balance_transaction' => 'txn_1ST6RlAns9lY52GQj55jM0xO',
      'charge' => 'py_1ST6RiPBokpe0rfCmhj17cES',
      'created' => 1763063082,
      'currency' => 'eur',
      'fee_source' => 
      array (
        'charge' => 'py_1ST6RiPBokpe0rfCmhj17cES',
        'type' => 'charge',
      ),
      'livemode' => true,
      'originating_transaction' => 'ch_3ST6QtAns9lY52GQ1Lz2I29B',
      'refunded' => false,
      'refunds' => 
      array (
        'object' => 'list',
        'data' => 
        array (
        ),
        'has_more' => false,
        'total_count' => 0,
        'url' => '/v1/application_fees/fee_1ST6RiPBokpe0rfC5IKgYOVm/refunds',
      ),
    ),
    'created_at' => '2026-01-07 19:14:52',
    'updated_at' => '2026-01-07 20:47:44',
  ),
  209 => 
  array (
    'id' => 210,
    'stripe_fee_id' => 'fee_1ST4ghPIzlXORG3aYMYeSICr',
    'amount' => '4.95',
    'currency' => 'EUR',
    'created_at_stripe' => '2026-01-07 21:47:44',
    'stripe_account_id' => 'acct_1OYpemPIzlXORG3a',
    'partner_email' => 'feusrl.2019@gmail.com',
    'partner_name' => 'Feu Srl',
    'client_id' => 301,
    'charge_id' => 'py_1ST4ghPIzlXORG3acHSFVSsU',
    'description' => 'feusrl.2019@gmail.com - acct_1OYpemPIzlXORG3a',
    'period_month' => '2025-11',
    'raw_data' => 
    array (
      'id' => 'fee_1ST4ghPIzlXORG3aYMYeSICr',
      'object' => 'application_fee',
      'account' => 'acct_1OYpemPIzlXORG3a',
      'amount' => 495,
      'amount_refunded' => 0,
      'application' => 'ca_Ox2Rdo90ojLoUICobfcdmeRJeF2Icbzs',
      'balance_transaction' => 'txn_1ST4gjAns9lY52GQmgge5YNP',
      'charge' => 'py_1ST4ghPIzlXORG3acHSFVSsU',
      'created' => 1763056323,
      'currency' => 'eur',
      'fee_source' => 
      array (
        'charge' => 'py_1ST4ghPIzlXORG3acHSFVSsU',
        'type' => 'charge',
      ),
      'livemode' => true,
      'originating_transaction' => 'ch_3ST1MeAns9lY52GQ1RUTtSmZ',
      'refunded' => false,
      'refunds' => 
      array (
        'object' => 'list',
        'data' => 
        array (
        ),
        'has_more' => false,
        'total_count' => 0,
        'url' => '/v1/application_fees/fee_1ST4ghPIzlXORG3aYMYeSICr/refunds',
      ),
    ),
    'created_at' => '2026-01-07 19:14:53',
    'updated_at' => '2026-01-07 20:47:44',
  ),
  210 => 
  array (
    'id' => 211,
    'stripe_fee_id' => 'fee_1ST3CFPEpkzElSu4gzEYreCG',
    'amount' => '3.16',
    'currency' => 'EUR',
    'created_at_stripe' => '2026-01-07 21:47:44',
    'stripe_account_id' => 'acct_1OrHzGPEpkzElSu4',
    'partner_email' => 'andreadellomodarme@gmail.com',
    'partner_name' => 'PUNTO P DI ANDREA DELL\'OMODARME',
    'client_id' => 383,
    'charge_id' => 'py_1ST3CFPEpkzElSu4ow7JAVeX',
    'description' => 'andreadellomodarme@gmail.com - acct_1OrHzGPEpkzElSu4',
    'period_month' => '2025-11',
    'raw_data' => 
    array (
      'id' => 'fee_1ST3CFPEpkzElSu4gzEYreCG',
      'object' => 'application_fee',
      'account' => 'acct_1OrHzGPEpkzElSu4',
      'amount' => 316,
      'amount_refunded' => 0,
      'application' => 'ca_Ox2Rdo90ojLoUICobfcdmeRJeF2Icbzs',
      'balance_transaction' => 'txn_1ST3CIAns9lY52GQI7KOhVLk',
      'charge' => 'py_1ST3CFPEpkzElSu4ow7JAVeX',
      'created' => 1763050591,
      'currency' => 'eur',
      'fee_source' => 
      array (
        'charge' => 'py_1ST3CFPEpkzElSu4ow7JAVeX',
        'type' => 'charge',
      ),
      'livemode' => true,
      'originating_transaction' => 'ch_3ST2sUAns9lY52GQ13pv3GHr',
      'refunded' => false,
      'refunds' => 
      array (
        'object' => 'list',
        'data' => 
        array (
        ),
        'has_more' => false,
        'total_count' => 0,
        'url' => '/v1/application_fees/fee_1ST3CFPEpkzElSu4gzEYreCG/refunds',
      ),
    ),
    'created_at' => '2026-01-07 19:14:53',
    'updated_at' => '2026-01-07 20:47:44',
  ),
  211 => 
  array (
    'id' => 212,
    'stripe_fee_id' => 'fee_1ST3BKPHe8tUH2x9WCbfDh44',
    'amount' => '3.77',
    'currency' => 'EUR',
    'created_at_stripe' => '2026-01-07 21:47:44',
    'stripe_account_id' => 'acct_1PHLvIPHe8tUH2x9',
    'partner_email' => 'michele@vadformaggi.it',
    'partner_name' => 'V.A.D. FORMAGGI SRL',
    'client_id' => 364,
    'charge_id' => 'py_1ST3BKPHe8tUH2x94S83oqX5',
    'description' => 'michele@vadformaggi.it - acct_1PHLvIPHe8tUH2x9',
    'period_month' => '2025-11',
    'raw_data' => 
    array (
      'id' => 'fee_1ST3BKPHe8tUH2x9WCbfDh44',
      'object' => 'application_fee',
      'account' => 'acct_1PHLvIPHe8tUH2x9',
      'amount' => 377,
      'amount_refunded' => 0,
      'application' => 'ca_Ox2Rdo90ojLoUICobfcdmeRJeF2Icbzs',
      'balance_transaction' => 'txn_1ST3BNAns9lY52GQx1Iq2XdB',
      'charge' => 'py_1ST3BKPHe8tUH2x94S83oqX5',
      'created' => 1763050534,
      'currency' => 'eur',
      'fee_source' => 
      array (
        'charge' => 'py_1ST3BKPHe8tUH2x94S83oqX5',
        'type' => 'charge',
      ),
      'livemode' => true,
      'originating_transaction' => 'ch_3ST1iZAns9lY52GQ1DmVWPVz',
      'refunded' => false,
      'refunds' => 
      array (
        'object' => 'list',
        'data' => 
        array (
        ),
        'has_more' => false,
        'total_count' => 0,
        'url' => '/v1/application_fees/fee_1ST3BKPHe8tUH2x9WCbfDh44/refunds',
      ),
    ),
    'created_at' => '2026-01-07 19:14:54',
    'updated_at' => '2026-01-07 20:47:44',
  ),
  212 => 
  array (
    'id' => 213,
    'stripe_fee_id' => 'fee_1SSfxqPEpkzElSu4WzKlzNeP',
    'amount' => '3.04',
    'currency' => 'EUR',
    'created_at_stripe' => '2026-01-07 21:47:44',
    'stripe_account_id' => 'acct_1OrHzGPEpkzElSu4',
    'partner_email' => 'andreadellomodarme@gmail.com',
    'partner_name' => 'PUNTO P DI ANDREA DELL\'OMODARME',
    'client_id' => 383,
    'charge_id' => 'py_1SSfxqPEpkzElSu43KQ6v5BZ',
    'description' => 'andreadellomodarme@gmail.com - acct_1OrHzGPEpkzElSu4',
    'period_month' => '2025-11',
    'raw_data' => 
    array (
      'id' => 'fee_1SSfxqPEpkzElSu4WzKlzNeP',
      'object' => 'application_fee',
      'account' => 'acct_1OrHzGPEpkzElSu4',
      'amount' => 304,
      'amount_refunded' => 0,
      'application' => 'ca_Ox2Rdo90ojLoUICobfcdmeRJeF2Icbzs',
      'balance_transaction' => 'txn_1SSfxtAns9lY52GQi4xxIRMZ',
      'charge' => 'py_1SSfxqPEpkzElSu43KQ6v5BZ',
      'created' => 1762961286,
      'currency' => 'eur',
      'fee_source' => 
      array (
        'charge' => 'py_1SSfxqPEpkzElSu43KQ6v5BZ',
        'type' => 'charge',
      ),
      'livemode' => true,
      'originating_transaction' => 'ch_3SSftZAns9lY52GQ1cRWPurL',
      'refunded' => false,
      'refunds' => 
      array (
        'object' => 'list',
        'data' => 
        array (
        ),
        'has_more' => false,
        'total_count' => 0,
        'url' => '/v1/application_fees/fee_1SSfxqPEpkzElSu4WzKlzNeP/refunds',
      ),
    ),
    'created_at' => '2026-01-07 19:14:55',
    'updated_at' => '2026-01-07 20:47:44',
  ),
  213 => 
  array (
    'id' => 214,
    'stripe_fee_id' => 'fee_1SSOApAcaExTZKe83ZksU30q',
    'amount' => '3.89',
    'currency' => 'EUR',
    'created_at_stripe' => '2026-01-07 21:47:44',
    'stripe_account_id' => 'acct_1QsMHWAcaExTZKe8',
    'partner_email' => 'nclcucc@gmail.com',
    'partner_name' => 'La Loggia sul Mare SRL',
    'client_id' => 322,
    'charge_id' => 'py_1SSOApAcaExTZKe8f8qwZrZ2',
    'description' => 'nclcucc@gmail.com - acct_1QsMHWAcaExTZKe8',
    'period_month' => '2025-11',
    'raw_data' => 
    array (
      'id' => 'fee_1SSOApAcaExTZKe83ZksU30q',
      'object' => 'application_fee',
      'account' => 'acct_1QsMHWAcaExTZKe8',
      'amount' => 389,
      'amount_refunded' => 0,
      'application' => 'ca_Ox2Rdo90ojLoUICobfcdmeRJeF2Icbzs',
      'balance_transaction' => 'txn_1SSOAsAns9lY52GQAGwN0Z02',
      'charge' => 'py_1SSOApAcaExTZKe8f8qwZrZ2',
      'created' => 1762892899,
      'currency' => 'eur',
      'fee_source' => 
      array (
        'charge' => 'py_1SSOApAcaExTZKe8f8qwZrZ2',
        'type' => 'charge',
      ),
      'livemode' => true,
      'originating_transaction' => 'ch_3SSNjMAns9lY52GQ1kWTMmqk',
      'refunded' => false,
      'refunds' => 
      array (
        'object' => 'list',
        'data' => 
        array (
        ),
        'has_more' => false,
        'total_count' => 0,
        'url' => '/v1/application_fees/fee_1SSOApAcaExTZKe83ZksU30q/refunds',
      ),
    ),
    'created_at' => '2026-01-07 19:14:55',
    'updated_at' => '2026-01-07 20:47:44',
  ),
  214 => 
  array (
    'id' => 215,
    'stripe_fee_id' => 'fee_1SSMYHPB8fwsTso3buystnYp',
    'amount' => '3.50',
    'currency' => 'EUR',
    'created_at_stripe' => '2026-01-07 21:47:44',
    'stripe_account_id' => 'acct_1Oe1NCPB8fwsTso3',
    'partner_email' => 'alepizza2000@gmail.com',
    'partner_name' => 'PIZZA E TORTA DA ALEPIZZA DI BIANCHI ALESSIO',
    'client_id' => 336,
    'charge_id' => 'py_1SSMYHPB8fwsTso3gc39peA9',
    'description' => 'alepizza2000@gmail.com - acct_1Oe1NCPB8fwsTso3',
    'period_month' => '2025-11',
    'raw_data' => 
    array (
      'id' => 'fee_1SSMYHPB8fwsTso3buystnYp',
      'object' => 'application_fee',
      'account' => 'acct_1Oe1NCPB8fwsTso3',
      'amount' => 350,
      'amount_refunded' => 0,
      'application' => 'ca_Ox2Rdo90ojLoUICobfcdmeRJeF2Icbzs',
      'balance_transaction' => 'txn_1SSMYKAns9lY52GQmN6JFqPJ',
      'charge' => 'py_1SSMYHPB8fwsTso3gc39peA9',
      'created' => 1762886665,
      'currency' => 'eur',
      'fee_source' => 
      array (
        'charge' => 'py_1SSMYHPB8fwsTso3gc39peA9',
        'type' => 'charge',
      ),
      'livemode' => true,
      'originating_transaction' => 'ch_3SSMCdAns9lY52GQ05Ft7hyO',
      'refunded' => false,
      'refunds' => 
      array (
        'object' => 'list',
        'data' => 
        array (
        ),
        'has_more' => false,
        'total_count' => 0,
        'url' => '/v1/application_fees/fee_1SSMYHPB8fwsTso3buystnYp/refunds',
      ),
    ),
    'created_at' => '2026-01-07 19:14:56',
    'updated_at' => '2026-01-07 20:47:44',
  ),
  215 => 
  array (
    'id' => 216,
    'stripe_fee_id' => 'fee_1SRrAmPEpkzElSu4VMldQxrl',
    'amount' => '3.27',
    'currency' => 'EUR',
    'created_at_stripe' => '2026-01-07 21:47:44',
    'stripe_account_id' => 'acct_1OrHzGPEpkzElSu4',
    'partner_email' => 'andreadellomodarme@gmail.com',
    'partner_name' => 'PUNTO P DI ANDREA DELL\'OMODARME',
    'client_id' => 383,
    'charge_id' => 'py_1SRrAmPEpkzElSu4Wv3Rblhb',
    'description' => 'andreadellomodarme@gmail.com - acct_1OrHzGPEpkzElSu4',
    'period_month' => '2025-11',
    'raw_data' => 
    array (
      'id' => 'fee_1SRrAmPEpkzElSu4VMldQxrl',
      'object' => 'application_fee',
      'account' => 'acct_1OrHzGPEpkzElSu4',
      'amount' => 327,
      'amount_refunded' => 0,
      'application' => 'ca_Ox2Rdo90ojLoUICobfcdmeRJeF2Icbzs',
      'balance_transaction' => 'txn_1SRrAqAns9lY52GQ2yNTwT4z',
      'charge' => 'py_1SRrAmPEpkzElSu4Wv3Rblhb',
      'created' => 1762766044,
      'currency' => 'eur',
      'fee_source' => 
      array (
        'charge' => 'py_1SRrAmPEpkzElSu4Wv3Rblhb',
        'type' => 'charge',
      ),
      'livemode' => true,
      'originating_transaction' => 'ch_3SRdSuAns9lY52GQ1rJ0oVfK',
      'refunded' => false,
      'refunds' => 
      array (
        'object' => 'list',
        'data' => 
        array (
        ),
        'has_more' => false,
        'total_count' => 0,
        'url' => '/v1/application_fees/fee_1SRrAmPEpkzElSu4VMldQxrl/refunds',
      ),
    ),
    'created_at' => '2026-01-07 19:14:57',
    'updated_at' => '2026-01-07 20:47:44',
  ),
  216 => 
  array (
    'id' => 217,
    'stripe_fee_id' => 'fee_1SRcxGPIzlXORG3aMbZ6nSpL',
    'amount' => '5.93',
    'currency' => 'EUR',
    'created_at_stripe' => '2026-01-07 21:47:44',
    'stripe_account_id' => 'acct_1OYpemPIzlXORG3a',
    'partner_email' => 'feusrl.2019@gmail.com',
    'partner_name' => 'Feu Srl',
    'client_id' => 301,
    'charge_id' => 'py_1SRcxGPIzlXORG3aVdgBmYfn',
    'description' => 'feusrl.2019@gmail.com - acct_1OYpemPIzlXORG3a',
    'period_month' => '2025-11',
    'raw_data' => 
    array (
      'id' => 'fee_1SRcxGPIzlXORG3aMbZ6nSpL',
      'object' => 'application_fee',
      'account' => 'acct_1OYpemPIzlXORG3a',
      'amount' => 593,
      'amount_refunded' => 0,
      'application' => 'ca_Ox2Rdo90ojLoUICobfcdmeRJeF2Icbzs',
      'balance_transaction' => 'txn_1SRcxIAns9lY52GQGTep8Pjx',
      'charge' => 'py_1SRcxGPIzlXORG3aVdgBmYfn',
      'created' => 1762711390,
      'currency' => 'eur',
      'fee_source' => 
      array (
        'charge' => 'py_1SRcxGPIzlXORG3aVdgBmYfn',
        'type' => 'charge',
      ),
      'livemode' => true,
      'originating_transaction' => 'ch_3SRcvLAns9lY52GQ0YyvleZo',
      'refunded' => false,
      'refunds' => 
      array (
        'object' => 'list',
        'data' => 
        array (
        ),
        'has_more' => false,
        'total_count' => 0,
        'url' => '/v1/application_fees/fee_1SRcxGPIzlXORG3aMbZ6nSpL/refunds',
      ),
    ),
    'created_at' => '2026-01-07 19:14:57',
    'updated_at' => '2026-01-07 20:47:44',
  ),
  217 => 
  array (
    'id' => 218,
    'stripe_fee_id' => 'fee_1SRcWBPB8fwsTso3EgURpmuX',
    'amount' => '3.71',
    'currency' => 'EUR',
    'created_at_stripe' => '2026-01-07 21:47:44',
    'stripe_account_id' => 'acct_1Oe1NCPB8fwsTso3',
    'partner_email' => 'alepizza2000@gmail.com',
    'partner_name' => 'PIZZA E TORTA DA ALEPIZZA DI BIANCHI ALESSIO',
    'client_id' => 336,
    'charge_id' => 'py_1SRcWBPB8fwsTso3oWlpgPMD',
    'description' => 'alepizza2000@gmail.com - acct_1Oe1NCPB8fwsTso3',
    'period_month' => '2025-11',
    'raw_data' => 
    array (
      'id' => 'fee_1SRcWBPB8fwsTso3EgURpmuX',
      'object' => 'application_fee',
      'account' => 'acct_1Oe1NCPB8fwsTso3',
      'amount' => 371,
      'amount_refunded' => 0,
      'application' => 'ca_Ox2Rdo90ojLoUICobfcdmeRJeF2Icbzs',
      'balance_transaction' => 'txn_1SRcWEAns9lY52GQ8XHGX8zi',
      'charge' => 'py_1SRcWBPB8fwsTso3oWlpgPMD',
      'created' => 1762709711,
      'currency' => 'eur',
      'fee_source' => 
      array (
        'charge' => 'py_1SRcWBPB8fwsTso3oWlpgPMD',
        'type' => 'charge',
      ),
      'livemode' => true,
      'originating_transaction' => 'ch_3SRcTvAns9lY52GQ1d0dkXKd',
      'refunded' => false,
      'refunds' => 
      array (
        'object' => 'list',
        'data' => 
        array (
        ),
        'has_more' => false,
        'total_count' => 0,
        'url' => '/v1/application_fees/fee_1SRcWBPB8fwsTso3EgURpmuX/refunds',
      ),
    ),
    'created_at' => '2026-01-07 19:14:58',
    'updated_at' => '2026-01-07 20:47:44',
  ),
  218 => 
  array (
    'id' => 219,
    'stripe_fee_id' => 'fee_1SRcECPEpkzElSu4XZ3Q0E0I',
    'amount' => '2.96',
    'currency' => 'EUR',
    'created_at_stripe' => '2026-01-07 21:47:44',
    'stripe_account_id' => 'acct_1OrHzGPEpkzElSu4',
    'partner_email' => 'andreadellomodarme@gmail.com',
    'partner_name' => 'PUNTO P DI ANDREA DELL\'OMODARME',
    'client_id' => 383,
    'charge_id' => 'py_1SRcEBPEpkzElSu4WPqDdBcj',
    'description' => 'andreadellomodarme@gmail.com - acct_1OrHzGPEpkzElSu4',
    'period_month' => '2025-11',
    'raw_data' => 
    array (
      'id' => 'fee_1SRcECPEpkzElSu4XZ3Q0E0I',
      'object' => 'application_fee',
      'account' => 'acct_1OrHzGPEpkzElSu4',
      'amount' => 296,
      'amount_refunded' => 0,
      'application' => 'ca_Ox2Rdo90ojLoUICobfcdmeRJeF2Icbzs',
      'balance_transaction' => 'txn_1SRcEEAns9lY52GQqyNxy1Qa',
      'charge' => 'py_1SRcEBPEpkzElSu4WPqDdBcj',
      'created' => 1762708596,
      'currency' => 'eur',
      'fee_source' => 
      array (
        'charge' => 'py_1SRcEBPEpkzElSu4WPqDdBcj',
        'type' => 'charge',
      ),
      'livemode' => true,
      'originating_transaction' => 'ch_3SRc3kAns9lY52GQ0zAYSsze',
      'refunded' => false,
      'refunds' => 
      array (
        'object' => 'list',
        'data' => 
        array (
        ),
        'has_more' => false,
        'total_count' => 0,
        'url' => '/v1/application_fees/fee_1SRcECPEpkzElSu4XZ3Q0E0I/refunds',
      ),
    ),
    'created_at' => '2026-01-07 19:14:59',
    'updated_at' => '2026-01-07 20:47:44',
  ),
  219 => 
  array (
    'id' => 220,
    'stripe_fee_id' => 'fee_1SRacNPEpkzElSu4fqeiAQSW',
    'amount' => '4.17',
    'currency' => 'EUR',
    'created_at_stripe' => '2026-01-07 21:47:44',
    'stripe_account_id' => 'acct_1OrHzGPEpkzElSu4',
    'partner_email' => 'andreadellomodarme@gmail.com',
    'partner_name' => 'PUNTO P DI ANDREA DELL\'OMODARME',
    'client_id' => 383,
    'charge_id' => 'py_1SRacMPEpkzElSu4YYL9ENgD',
    'description' => 'andreadellomodarme@gmail.com - acct_1OrHzGPEpkzElSu4',
    'period_month' => '2025-11',
    'raw_data' => 
    array (
      'id' => 'fee_1SRacNPEpkzElSu4fqeiAQSW',
      'object' => 'application_fee',
      'account' => 'acct_1OrHzGPEpkzElSu4',
      'amount' => 417,
      'amount_refunded' => 0,
      'application' => 'ca_Ox2Rdo90ojLoUICobfcdmeRJeF2Icbzs',
      'balance_transaction' => 'txn_1SRacPAns9lY52GQvnohUKhF',
      'charge' => 'py_1SRacMPEpkzElSu4YYL9ENgD',
      'created' => 1762702407,
      'currency' => 'eur',
      'fee_source' => 
      array (
        'charge' => 'py_1SRacMPEpkzElSu4YYL9ENgD',
        'type' => 'charge',
      ),
      'livemode' => true,
      'originating_transaction' => 'ch_3SRWozAns9lY52GQ13w4iGzS',
      'refunded' => false,
      'refunds' => 
      array (
        'object' => 'list',
        'data' => 
        array (
        ),
        'has_more' => false,
        'total_count' => 0,
        'url' => '/v1/application_fees/fee_1SRacNPEpkzElSu4fqeiAQSW/refunds',
      ),
    ),
    'created_at' => '2026-01-07 19:15:00',
    'updated_at' => '2026-01-07 20:47:44',
  ),
);

        DB::beginTransaction();
        
        try {
            // Importa transazioni Stripe
            $transCreated = 0;
            $transUpdated = 0;

            foreach ($transactions as $transData) {
                // Converti metadata in JSON se è array
                if (isset($transData['metadata']) && is_array($transData['metadata'])) {
                    $transData['metadata'] = json_encode($transData['metadata']);
                }

                $existing = DB::table('stripe_transactions')
                    ->where('transaction_id', $transData['transaction_id'])
                    ->first();

                if ($existing) {
                    DB::table('stripe_transactions')
                        ->where('transaction_id', $transData['transaction_id'])
                        ->update($transData);
                    $transUpdated++;
                } else {
                    DB::table('stripe_transactions')->insert($transData);
                    $transCreated++;
                }
            }

            // Importa application fees
            $feesCreated = 0;
            $feesUpdated = 0;
            $feesSkipped = 0;

            foreach ($fees as $feeData) {
                // Converti raw_data in JSON se è array
                if (isset($feeData['raw_data']) && is_array($feeData['raw_data'])) {
                    $feeData['raw_data'] = json_encode($feeData['raw_data']);
                }

                // Risolvi client_id: cerca cliente per email o imposta NULL
                if (!empty($feeData['partner_email'])) {
                    $client = DB::table('clients')->where('email', $feeData['partner_email'])->first();
                    $feeData['client_id'] = $client ? $client->id : null;
                } else {
                    $feeData['client_id'] = null;
                }

                $existing = DB::table('application_fees')
                    ->where('stripe_fee_id', $feeData['stripe_fee_id'])
                    ->first();

                if ($existing) {
                    DB::table('application_fees')
                        ->where('stripe_fee_id', $feeData['stripe_fee_id'])
                        ->update($feeData);
                    $feesUpdated++;
                } else {
                    DB::table('application_fees')->insert($feeData);
                    $feesCreated++;
                }
            }

            DB::commit();

            $this->command->info("Importazione completata!");
            $this->command->info("   Transazioni Stripe:");
            $this->command->info("     - Create: {$transCreated}");
            $this->command->info("     - Aggiornate: {$transUpdated}");
            $this->command->info("   Application Fees:");
            $this->command->info("     - Create: {$feesCreated}");
            $this->command->info("     - Aggiornate: {$feesUpdated}");
            $this->command->info("     - Senza cliente: {$feesSkipped}");

        } catch (\Exception $e) {
            DB::rollBack();
            $this->command->error('❌ Errore durante l\'importazione: ' . $e->getMessage());
            throw $e;
        }
    }
}
