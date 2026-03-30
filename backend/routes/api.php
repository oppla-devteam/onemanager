<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\ClientController;
use App\Http\Controllers\API\PartnerController;
use App\Http\Controllers\API\RestaurantController;
use App\Http\Controllers\API\InvoiceController;
use App\Http\Controllers\API\DeliveryController;
use App\Http\Controllers\API\TaskController;
use App\Http\Controllers\API\TaskBoardController;
use App\Http\Controllers\API\PaymentController;
use App\Http\Controllers\AccountingController;
use App\Http\Controllers\ContractController;
use App\Http\Controllers\ContractTemplateController;
use App\Http\Controllers\ContractSignatureController;
use App\Http\Controllers\LeadController;
use App\Http\Controllers\OpportunityController;
use App\Http\Controllers\ActivityController;
use App\Http\Controllers\CampaignController;
use App\Http\Controllers\FattureInCloudController;
use App\Http\Controllers\InvoicingReportController;
use App\Http\Controllers\PushNotificationController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DeliveryDashboardController;

use App\Http\Controllers\API\DatabaseController;
use App\Http\Controllers\API\OnboardingController;
use App\Http\Controllers\API\SyncController;
use App\Http\Controllers\API\OnboardingFlowController;
use App\Http\Controllers\API\RestaurantClosureController;
use App\Http\Controllers\API\FeeClassController;
use App\Http\Controllers\API\OpplaController;
use App\Http\Controllers\API\OpplaSyncController;
use App\Http\Controllers\API\OpplaWriteController;
use App\Http\Controllers\API\OrderController;
use App\Http\Controllers\API\DeliveryZoneController;
use App\Http\Controllers\API\OpplaUsersController;
use App\Http\Controllers\WebhookController;
use App\Http\Controllers\StripeWebhookController;
use App\Http\Controllers\StripeReportController;
use App\Http\Controllers\ClientImportController;
use App\Http\Controllers\RiderController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\SupplierInvoiceController;
use App\Http\Controllers\API\MenuController;
use App\Http\Controllers\API\PartnerProtectionController;
use App\Http\Controllers\API\UserController;
use App\Http\Controllers\API\BinkAuthController;
use App\Http\Controllers\API\CancellationController;
use App\Http\Controllers\API\CustomerOrderController;
use App\Http\Controllers\API\PartnerReportController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Public routes (rate-limited)
Route::middleware('throttle:10,1')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/auth/bink', [BinkAuthController::class, 'authenticate']);
});

// Webhooks (no auth required)
Route::post('/webhooks/fatture-in-cloud', [WebhookController::class, 'handleFattureInCloudWebhook']);
Route::post('/webhooks/stripe', [StripeWebhookController::class, 'handleWebhook']);
Route::post('/register', [AuthController::class, 'register']);

// Protected data routes (require auth:sanctum)
Route::middleware('auth:sanctum')->group(function () {
    // Orders routes
    Route::prefix('orders')->group(function () {
        Route::get('/', [OrderController::class, 'index']);
        Route::get('/stats', [OrderController::class, 'stats']);
        Route::get('/export', [OrderController::class, 'export']);
        Route::get('/{id}', [OrderController::class, 'show']);
        Route::post('/sync', [OrderController::class, 'sync']);
        Route::delete('/{id}', [OrderController::class, 'destroy']);
    });

    // Stripe routes
    Route::prefix('stripe')->group(function () {
        Route::get('/status', [PaymentController::class, 'checkStripeStatus']);
        Route::post('/sync', [PaymentController::class, 'importStripe']);
        Route::post('/import', [PaymentController::class, 'importStripe']);
        Route::post('/refund', [PaymentController::class, 'refundPayment']);
        Route::get('/stats', [PaymentController::class, 'stats']);

        Route::post('/ordinary-invoices/pregenerate/{year}/{month}', [\App\Http\Controllers\StripeOrdinaryInvoiceController::class, 'pregenerate']);
        Route::post('/ordinary-invoices/generate/{year}/{month}', [\App\Http\Controllers\StripeOrdinaryInvoiceController::class, 'generate']);
        Route::post('/ordinary-invoices/generate-single/{year}/{month}', [\App\Http\Controllers\StripeOrdinaryInvoiceController::class, 'generateSingle']);
        Route::post('/ordinary-invoices/send-to-fic/{year}/{month}', [\App\Http\Controllers\StripeOrdinaryInvoiceController::class, 'sendToFIC']);
    });

    // Payment routes
    Route::prefix('payments')->group(function () {
        Route::post('/import-csv', [PaymentController::class, 'importCSV']);
        Route::post('/import-stripe-commissions', [PaymentController::class, 'importStripeCommissions']);
        Route::get('/aggregate-by-client', [PaymentController::class, 'aggregateByClient']);
        Route::get('/aggregate-by-destination', [PaymentController::class, 'aggregateByDestination']);
        Route::get('/aggregate-commissions-by-partner', [PaymentController::class, 'aggregateCommissionsByPartner']);
        Route::get('/application-fees', [PaymentController::class, 'getApplicationFees']);
        Route::get('/application-fees-detailed', [PaymentController::class, 'getApplicationFeesDetailed']);
        Route::get('/application-fees-export', [PaymentController::class, 'exportApplicationFees']);

        Route::post('/commission-invoices/pregenerate/{year}/{month}', [PaymentController::class, 'pregenerateCommissionInvoices']);
        Route::post('/commission-invoices/generate/{year}/{month}', [PaymentController::class, 'generateCommissionInvoices']);
        Route::post('/commission-invoices/generate-single/{year}/{month}', [PaymentController::class, 'generateSingleCommissionInvoice']);
        Route::post('/commission-invoices/send-to-fic/{year}/{month}', [PaymentController::class, 'sendCommissionInvoicesToFIC']);

        Route::get('/dashboard-stats', [PaymentController::class, 'dashboardStats']);
    });

    // Database PostgreSQL routes (Read-Only)
    Route::prefix('database')->group(function () {
        Route::get('/test', [DatabaseController::class, 'testConnection']);
        Route::get('/partners', [DatabaseController::class, 'getPartners']);
        Route::get('/partners/{id}', [DatabaseController::class, 'getPartner']);
        Route::get('/partners/search/{query}', [DatabaseController::class, 'searchPartners']);
        Route::get('/stats', [DatabaseController::class, 'getStats']);
        Route::post('/sync', [DatabaseController::class, 'syncPartners']);
    });

    // OPPLA Database routes (PostgreSQL Read-Only)
    Route::prefix('oppla')->group(function () {
        Route::get('/clients', [OpplaController::class, 'getClients']);
        Route::get('/test', [OpplaController::class, 'testConnection']);
        Route::get('/users', [OpplaUsersController::class, 'getUsers']);
        Route::get('/restaurants', [OpplaUsersController::class, 'getRestaurants']);
        Route::get('/users-with-restaurants', [OpplaUsersController::class, 'getUsersWithRestaurants']);
    });

    // OPPLA Sync routes (extra rate limiting)
    Route::prefix('oppla/sync')->middleware('throttle:sync')->group(function () {
        Route::post('/database', [OpplaSyncController::class, 'syncDatabase']);
        Route::post('/all', [OpplaSyncController::class, 'syncAll']);
        Route::post('/clients', [OpplaSyncController::class, 'syncClients']);
        Route::get('/test', [OpplaSyncController::class, 'testConnection']);
    });

    // OPPLA Write Operations (REQUIRES CONFIRMATION)
    Route::prefix('oppla/write')->group(function () {
        Route::post('/request-confirmation', [OpplaWriteController::class, 'requestConfirmation']);
        Route::post('/execute', [OpplaWriteController::class, 'execute']);
    });

    // OPPLA Direct Write Endpoints (with confirmation)
    Route::prefix('oppla')->group(function () {
        Route::post('/restaurants/{id}/update', [OpplaWriteController::class, 'updateRestaurant']);
        Route::post('/partners/create', [OpplaWriteController::class, 'createPartner']);
        Route::post('/partners/{id}/update', [OpplaWriteController::class, 'updatePartner']);
        Route::post('/orders/{id}/update-status', [OpplaWriteController::class, 'updateOrderStatus']);
        Route::delete('/orders/{id}', [OpplaWriteController::class, 'deleteOrder']);
    });

    // Customer Ordering (accessible to all authenticated users)
    Route::prefix('customer')->group(function () {
        Route::get('/shops', [CustomerOrderController::class, 'browseShops']);
        Route::get('/shops/{restaurantId}/menu', [CustomerOrderController::class, 'viewMenu']);
        Route::post('/orders', [CustomerOrderController::class, 'placeOrder']);
        Route::get('/orders/track', [CustomerOrderController::class, 'trackOrder']);
    });
});

// Delivery Zones routes (PUBLIC - for onboarding)
Route::prefix('delivery-zones')->group(function () {
    Route::get('/', [DeliveryZoneController::class, 'index']); // Public per onboarding
});

// Public contract signature routes (no auth required)
Route::prefix('contracts/sign')->group(function () {
    Route::get('/{token}', [ContractSignatureController::class, 'showSignaturePage']);
    Route::post('/{token}/request-otp', [ContractSignatureController::class, 'requestOtp']);
    Route::post('/{token}/sign', [ContractSignatureController::class, 'sign']);
    Route::post('/{token}/decline', [ContractSignatureController::class, 'decline']);
});

// Fatture in Cloud OAuth (auth handled manually with token param + session)
Route::prefix('fatture-in-cloud')->middleware('web')->group(function () {
    Route::get('/authorize', [FattureInCloudController::class, 'authorize']);
    Route::get('/callback', [FattureInCloudController::class, 'callback']);
});

// Onboarding (Public - no auth required)
Route::post('/onboarding', [OnboardingController::class, 'store']);

// Partners (public routes for onboarding)
Route::get('/partners', [PartnerController::class, 'index']);
Route::get('/partners/{id}', [PartnerController::class, 'show']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    // Auth
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
    Route::get('/users', [AuthController::class, 'getAllUsers']);

    // API Tokens (for MCP server / external integrations)
    Route::get('/api-tokens', [AuthController::class, 'listApiTokens']);
    Route::post('/api-tokens', [AuthController::class, 'createApiToken']);
    Route::delete('/api-tokens/{id}', [AuthController::class, 'revokeApiToken']);

    // Admin User Management
    Route::prefix('admin/users')->group(function () {
        Route::get('/', [UserController::class, 'index']);
        Route::post('/', [UserController::class, 'store']);
        Route::put('/{id}', [UserController::class, 'update']);
        Route::delete('/{id}', [UserController::class, 'destroy']);
    });

    // Admin Bink Users Management
    Route::prefix('admin/bink-users')->group(function () {
        Route::get('/', [BinkAuthController::class, 'listUsers']);
        Route::post('/', [BinkAuthController::class, 'addUser']);
        Route::put('/{id}', [BinkAuthController::class, 'updateUser']);
        Route::delete('/{id}', [BinkAuthController::class, 'removeUser']);
    });

    // Clients
    Route::get('/clients/export', [ClientController::class, 'export']);
    Route::apiResource('clients', ClientController::class);
    Route::get('/clients-stats', [ClientController::class, 'stats']);
    
    // Client Import
    Route::post('/clients/import/csv', [ClientImportController::class, 'importCsv']);
    Route::post('/clients/import/json', [ClientImportController::class, 'importJson']);

    // Partners
    Route::apiResource('partners', PartnerController::class)->except(['store']);
    Route::get('/partners-stats', [PartnerController::class, 'stats']);
    Route::post('/partners/{id}/assign-client', [PartnerController::class, 'assignClient']);
    Route::post('/partners/{id}/unassign-client', [PartnerController::class, 'unassignClient']);

    // Restaurants Management
    Route::prefix('restaurants')->group(function () {
        Route::get('/unassigned', [RestaurantController::class, 'getUnassigned']);
        Route::post('/assign', [RestaurantController::class, 'assignToClient']);
        Route::delete('/{restaurant}/unassign', [RestaurantController::class, 'unassignFromClient']);
        Route::put('/{restaurant}/reassign', [RestaurantController::class, 'reassignToClient']);
        
        // Chiusura massiva ristoranti
        Route::post('/close-period', [RestaurantClosureController::class, 'closePeriod']);
        Route::get('/close-status/{jobId}', [RestaurantClosureController::class, 'checkStatus']);
        Route::post('/save-holiday-mapping', [RestaurantClosureController::class, 'saveHolidayMapping']);
        Route::post('/reopen-batch/{batchId}', [RestaurantClosureController::class, 'reopenBatch']);
        Route::get('/reopen-status/{jobId}', [RestaurantClosureController::class, 'checkReopenStatus']);
        Route::get('/closure-batches', [RestaurantClosureController::class, 'getBatches']);
        Route::get('/check-python', [RestaurantClosureController::class, 'checkPython']);

        // Chiusura singolo ristorante
        Route::post('/{restaurantId}/close', [RestaurantClosureController::class, 'closeSingle']);
        Route::delete('/holidays/{holidayId}', [RestaurantClosureController::class, 'reopenSingle']);
    });

    // Menu Management
    Route::prefix('menus')->group(function () {
        Route::get('/', [MenuController::class, 'index']);
        Route::post('/', [MenuController::class, 'store']);
        Route::get('/categories', [MenuController::class, 'getCategories']);
        Route::get('/import-history', [MenuController::class, 'getImportHistory']);
        Route::post('/import', [MenuController::class, 'importCsv']);
        Route::get('/export', [MenuController::class, 'exportCsv']);
        Route::post('/bulk-update', [MenuController::class, 'bulkUpdate']);
        Route::get('/{id}', [MenuController::class, 'show']);
        Route::put('/{id}', [MenuController::class, 'update']);
        Route::delete('/{id}', [MenuController::class, 'destroy']);
    });

    // Invoices
    Route::get('/invoices/export', [InvoiceController::class, 'export']);
    Route::apiResource('invoices', InvoiceController::class);
    Route::get('/invoices-stats', [InvoiceController::class, 'stats']);
    Route::post('/invoices/{id}/send-to-fic', [InvoiceController::class, 'sendToFIC']); // Solo FIC
    Route::post('/invoices/{id}/retry-next-day', [InvoiceController::class, 'retryWithNextDay']); // Retry con data successiva
    Route::post('/invoices/{id}/send-sdi', [InvoiceController::class, 'sendToSDI']); // FIC + SDI (tutto)
    Route::post('/invoices/{id}/confirm', [InvoiceController::class, 'confirmInvoice']); // Verifica e SDI
    Route::post('/invoices/{id}/mark-paid', [InvoiceController::class, 'markAsPaid']);
    Route::post('/invoices/bulk-update-dates', [InvoiceController::class, 'bulkUpdateDates']); // Modifica date in bulk
    Route::get('/invoices/{id}/pdf', [InvoiceController::class, 'generatePDF']);
    Route::get('/invoices/{id}/download-pdf', [InvoiceController::class, 'downloadPDF']);
    Route::get('/invoices/{id}/download-pdf-fic', [InvoiceController::class, 'downloadPdfFromFIC']);
    Route::get('/invoices/{id}/sdi-status', [InvoiceController::class, 'checkSDIStatus']);
    Route::post('/invoices/{id}/credit-note', [InvoiceController::class, 'createCreditNote']);
    Route::post('/invoices/generate-monthly-deferred', [InvoiceController::class, 'generateMonthlyDeferred']);
    Route::post('/invoices/preview-from-payments', [InvoiceController::class, 'previewFromPayments']);
    Route::post('/invoices/generate-from-payments', [InvoiceController::class, 'generateFromPayments']);
    Route::post('/invoices/sync-fic', [InvoiceController::class, 'syncWithFIC']);


    // Deliveries
    Route::get('/deliveries/export', [DeliveryController::class, 'export']);
    Route::get('/deliveries/invoices/pregenerate', [DeliveryController::class, 'pregenerateInvoices']);
    Route::post('/deliveries/invoices/generate', [DeliveryController::class, 'generateInvoices']);
    Route::apiResource('deliveries', DeliveryController::class);
    Route::get('/deliveries-stats', [DeliveryController::class, 'stats']);

    // Contract Management System
    Route::prefix('contracts')->group(function () {
        // Contract CRUD
        Route::get('/', [ContractController::class, 'index']);
        Route::post('/', [ContractController::class, 'store']);
        Route::get('/export', [ContractController::class, 'export']);
        Route::get('/statistics', [ContractController::class, 'statistics']);
        Route::get('/clients', [ContractController::class, 'getClients']);
        Route::get('/{contract}', [ContractController::class, 'show']);
        Route::put('/{contract}', [ContractController::class, 'update']);
        Route::delete('/{contract}', [ContractController::class, 'destroy']);
        
        // Contract actions
        Route::post('/{contract}/prepare', [ContractController::class, 'prepare']);
        Route::post('/{contract}/send', [ContractController::class, 'send']);
        Route::post('/{contract}/send-for-signature', [ContractController::class, 'sendForSignatureEmail']);
        Route::post('/{contract}/activate', [ContractController::class, 'activate']);
        Route::post('/{contract}/terminate', [ContractController::class, 'terminate']);
        Route::post('/{contract}/cancel', [ContractController::class, 'cancel']);
        Route::post('/{contract}/renew', [ContractController::class, 'renew']);
        Route::post('/{contract}/duplicate', [ContractController::class, 'duplicate']);
        
        // PDF operations
        Route::get('/{contract}/pdf/download', [ContractController::class, 'downloadPdf']);
        Route::get('/{contract}/pdf/view', [ContractController::class, 'viewPdf']);
        
        // Signatures management
        Route::get('/{contract}/signatures', [ContractSignatureController::class, 'index']);
        Route::post('/{contract}/signatures', [ContractSignatureController::class, 'store']);
        Route::post('/signatures/{signature}/request-resign', [ContractSignatureController::class, 'requestResign']);
        Route::delete('/signatures/{signature}', [ContractSignatureController::class, 'destroy']);
        
        // Contract Renewals
        Route::get('/renewals/stats', [\App\Http\Controllers\ContractRenewalController::class, 'stats']);
        Route::get('/renewals/expiring', [\App\Http\Controllers\ContractRenewalController::class, 'expiring']);
        Route::get('/renewals/expired', [\App\Http\Controllers\ContractRenewalController::class, 'expired']);
        Route::post('/{contract}/manual-renew', [\App\Http\Controllers\ContractRenewalController::class, 'manualRenew']);
        Route::post('/{contract}/cancel-auto-renew', [\App\Http\Controllers\ContractRenewalController::class, 'cancelAutoRenew']);
        Route::post('/{contract}/enable-auto-renew', [\App\Http\Controllers\ContractRenewalController::class, 'enableAutoRenew']);
    });
    
    // Contract Templates
    Route::prefix('contract-templates')->group(function () {
        Route::get('/', [ContractTemplateController::class, 'index']);
        Route::post('/', [ContractTemplateController::class, 'store']);
        Route::get('/{template}', [ContractTemplateController::class, 'show']);
        Route::put('/{template}', [ContractTemplateController::class, 'update']);
        Route::delete('/{template}', [ContractTemplateController::class, 'destroy']);
        Route::post('/{template}/preview', [ContractTemplateController::class, 'preview']);
        Route::post('/{template}/duplicate', [ContractTemplateController::class, 'duplicate']);
    });

    // Tasks & Boards
    Route::apiResource('task-boards', TaskBoardController::class);
    Route::post('/task-boards/{id}/assign-users', [TaskBoardController::class, 'assignUsers']);
    Route::get('/task-boards/{id}/users', [TaskBoardController::class, 'getUsers']);
    Route::apiResource('tasks', TaskController::class);
    Route::get('/tasks-stats', [TaskController::class, 'stats']);

    // Payments
    Route::get('/payments/export-csv', [PaymentController::class, 'exportCsv']);
    Route::apiResource('payments', PaymentController::class);
    Route::get('/payments-stats', [PaymentController::class, 'stats']);

    // Accounting
    Route::prefix('accounting')->group(function () {
        Route::get('/dashboard', [AccountingController::class, 'dashboard']);
        Route::get('/financial-report', [AccountingController::class, 'financialReport']);
        
        // Bank Accounts
        Route::get('/accounts', [AccountingController::class, 'accounts']);
        Route::post('/accounts', [AccountingController::class, 'storeAccount']);
        
        // Transactions
        Route::get('/transactions', [AccountingController::class, 'transactions']);
        Route::put('/transactions/{id}', [AccountingController::class, 'updateTransaction']);
        
        // Import
        Route::post('/import-statement', [AccountingController::class, 'importStatement']);
        
        // Reconciliation
        Route::post('/reconcile', [AccountingController::class, 'reconcile']);
        Route::post('/auto-reconcile', [AccountingController::class, 'autoReconcile']);
        Route::get('/reconciliation-report', [AccountingController::class, 'reconciliationReport']);
        
        // Categories
        Route::get('/categories', [AccountingController::class, 'categories']);
        Route::post('/categories', [AccountingController::class, 'storeCategory']);
    });

    // CRM - Leads
    Route::prefix('crm/leads')->group(function () {
        Route::get('/', [LeadController::class, 'index']);
        Route::post('/', [LeadController::class, 'store']);
        Route::get('/export', [LeadController::class, 'export']);
        Route::get('/stats', [LeadController::class, 'stats']);
        Route::get('/{lead}', [LeadController::class, 'show']);
        Route::put('/{lead}', [LeadController::class, 'update']);
        Route::delete('/{lead}', [LeadController::class, 'destroy']);
        
        // Lead conversions
        Route::post('/{lead}/convert-to-client', [LeadController::class, 'convertToClient']);
        Route::post('/{lead}/convert-to-opportunity', [LeadController::class, 'convertToOpportunity']);
    });

    // CRM - Opportunities
    Route::prefix('crm/opportunities')->group(function () {
        Route::get('/', [OpportunityController::class, 'index']);
        Route::post('/', [OpportunityController::class, 'store']);
        Route::get('/stats', [OpportunityController::class, 'stats']);
        Route::get('/{opportunity}', [OpportunityController::class, 'show']);
        Route::put('/{opportunity}', [OpportunityController::class, 'update']);
        Route::delete('/{opportunity}', [OpportunityController::class, 'destroy']);
        
        // Opportunity actions
        Route::post('/{opportunity}/move-stage', [OpportunityController::class, 'moveStage']);
        Route::post('/{opportunity}/mark-as-won', [OpportunityController::class, 'markAsWon']);
        Route::post('/{opportunity}/mark-as-lost', [OpportunityController::class, 'markAsLost']);
    });

    // CRM - Activities
    Route::prefix('crm/activities')->group(function () {
        Route::get('/', [ActivityController::class, 'index']);
        Route::post('/', [ActivityController::class, 'store']);
        Route::get('/stats', [ActivityController::class, 'stats']);
        Route::get('/{activity}', [ActivityController::class, 'show']);
        Route::put('/{activity}', [ActivityController::class, 'update']);
        Route::delete('/{activity}', [ActivityController::class, 'destroy']);
        Route::post('/{activity}/complete', [ActivityController::class, 'complete']);
    });

    // CRM - Campaigns
    Route::prefix('crm/campaigns')->group(function () {
        Route::get('/', [CampaignController::class, 'index']);
        Route::post('/', [CampaignController::class, 'store']);
        Route::get('/stats', [CampaignController::class, 'stats']);
        Route::get('/{campaign}', [CampaignController::class, 'show']);
        Route::put('/{campaign}', [CampaignController::class, 'update']);
        Route::delete('/{campaign}', [CampaignController::class, 'destroy']);
        
        // Campaign members
        Route::post('/{campaign}/members', [CampaignController::class, 'addMembers']);
        Route::put('/{campaign}/members/{member}', [CampaignController::class, 'updateMemberStatus']);
    });

    // Fatture in Cloud Integration (protected endpoints)
    Route::prefix('fatture-in-cloud')->group(function () {
        Route::get('/status', [FattureInCloudController::class, 'status']);
        Route::post('/disconnect', [FattureInCloudController::class, 'disconnect']);
        
        // Invoices
        Route::get('/invoices', [FattureInCloudController::class, 'getInvoices']);
        Route::post('/invoices', [FattureInCloudController::class, 'createInvoice']);
        
        // Clients
        Route::get('/clients', [FattureInCloudController::class, 'getClients']);
        Route::post('/clients', [FattureInCloudController::class, 'createClient']);
        
        // Suppliers
        Route::get('/suppliers', [FattureInCloudController::class, 'getSuppliers']);
        
        // Received documents
        Route::get('/received-documents', [FattureInCloudController::class, 'getReceivedDocuments']);
        Route::post('/sync-passive-invoices', [FattureInCloudController::class, 'syncPassiveInvoices']);
    });

    // Reports
    Route::prefix('reports')->group(function () {
        Route::get('/invoicing', [InvoicingReportController::class, 'generateDetailedReport']);
        Route::get('/invoicing/export', [InvoicingReportController::class, 'exportToExcel']);
    });

    // Push Notifications
    Route::prefix('push-subscriptions')->group(function () {
        Route::post('/', [PushNotificationController::class, 'subscribe']);
        Route::delete('/', [PushNotificationController::class, 'unsubscribe']);
    });

    // Dashboard KPIs
    Route::get('/dashboard/unified', [DashboardController::class, 'getUnifiedDashboard']);
    Route::get('/dashboard/economic-kpis', [DashboardController::class, 'getEconomicKPIs']);
    
    // Delivery Operations Dashboard
    Route::get('/dashboard/delivery-ops', [DeliveryDashboardController::class, 'getOperationalKPIs']);
    Route::get('/dashboard/delivery-monthly', [DeliveryDashboardController::class, 'getMonthlySummary']);
    Route::get('/dashboard/riders', [DeliveryDashboardController::class, 'getRidersStatus']);

    // Riders Management (Tookan Integration)
    Route::prefix('riders')->group(function () {
        Route::get('/', [RiderController::class, 'index']);
        Route::get('/export', [RiderController::class, 'export']);
        Route::post('/sync-now', [RiderController::class, 'syncNow']);
        Route::get('/test-connection', [RiderController::class, 'testConnection']);
        Route::get('/realtime', [RiderController::class, 'getRealtime']);
        Route::get('/unassigned-tasks', [RiderController::class, 'getUnassignedTasks']);
        Route::get('/logs', [RiderController::class, 'getLogs']);
        Route::post('/', [RiderController::class, 'store']);
        Route::post('/notify', [RiderController::class, 'sendNotification']);
        Route::post('/assign-task', [RiderController::class, 'assignTask']);
        Route::post('/assign-team', [RiderController::class, 'assignRiderToTeam']);

        // Teams management
        Route::get('/teams', [RiderController::class, 'getTeams']);
        Route::post('/teams', [RiderController::class, 'createTeam']);
        Route::put('/teams/{teamId}', [RiderController::class, 'updateTeam']);
        Route::delete('/teams/{teamId}', [RiderController::class, 'deleteTeam']);

        // Single rider routes (must be after specific routes)
        Route::get('/{fleetId}', [RiderController::class, 'show']);
        Route::put('/{fleetId}', [RiderController::class, 'update']);
        Route::delete('/{fleetId}', [RiderController::class, 'destroy']);
        Route::post('/{fleetId}/toggle-block', [RiderController::class, 'toggleBlock']);
        Route::get('/{fleetId}/tasks', [RiderController::class, 'getTasks']);
        Route::get('/{fleetId}/location', [RiderController::class, 'getLocation']);
    });


    // Stripe Checkout Sessions (Link di pagamento)
    Route::prefix('stripe/checkout-sessions')->group(function () {
        Route::post('/', [PaymentController::class, 'createCheckoutSession']);
        Route::get('/', [PaymentController::class, 'listCheckoutSessions']);
        Route::post('/{sessionId}/refresh', [PaymentController::class, 'refreshCheckoutSession']);
    });

    // Stripe Reports
    Route::prefix('stripe-report')->group(function () {
        Route::get('/{year}/{month}', [StripeReportController::class, 'getMonthlyReport']);
        Route::post('/{year}/{month}/normalize', [StripeReportController::class, 'normalizeTransactions']);
        Route::post('/reset', [StripeReportController::class, 'resetNormalizations']);
        Route::put('/transaction/{id}', [StripeReportController::class, 'updateTransactionType']);
        Route::get('/{year}/{month}/export', [StripeReportController::class, 'exportToExcel']);
        Route::post('/{year}/{month}/send', [StripeReportController::class, 'sendToAccountant']);
        Route::get('/accountant-email', [StripeReportController::class, 'accountantEmail']);
        Route::post('/accountant-email', [StripeReportController::class, 'accountantEmail']);
    });

    // Onboarding Flow - OPPLA Delivery Platform
    // Flow: 1) Create client+partner (syncs to Oppla, invite email sent) → 2) Confirm Stripe Connect → 3) Create restaurant + finalize
    Route::prefix('onboarding')->group(function () {
        Route::post('/step-1-client-partner', [OnboardingFlowController::class, 'storeClientAndPartner']);
        Route::get('/step-2-stripe-status/{sessionId}', [OnboardingFlowController::class, 'checkStripeStatus']);
        Route::post('/step-2-stripe-confirm', [OnboardingFlowController::class, 'confirmStripe']);
        Route::post('/step-3-restaurant-finalize', [OnboardingFlowController::class, 'createRestaurantAndFinalize']);
        Route::get('/delivery-zones', [OnboardingFlowController::class, 'getDeliveryZones']);
        Route::get('/session/{sessionId}', [OnboardingFlowController::class, 'getSessionStatus']);
    });

    // Fee Classes Management
    Route::prefix('fee-classes')->group(function () {
        Route::get('/', [FeeClassController::class, 'index']);
        Route::post('/', [FeeClassController::class, 'store']);
        Route::get('/for-configuration', [FeeClassController::class, 'getForConfiguration']);
        Route::get('/{id}', [FeeClassController::class, 'show']);
        Route::put('/{id}', [FeeClassController::class, 'update']);
        Route::delete('/{id}', [FeeClassController::class, 'destroy']);
    });

    // Suppliers Management (Fornitori)
    Route::prefix('suppliers')->group(function () {
        Route::get('/', [SupplierController::class, 'index']);
        Route::post('/', [SupplierController::class, 'store']);
        Route::get('/export', [SupplierController::class, 'export']);
        Route::get('/stats', [SupplierController::class, 'stats']);
        Route::get('/search', [SupplierController::class, 'search']);
        Route::get('/{supplier}', [SupplierController::class, 'show']);
        Route::put('/{supplier}', [SupplierController::class, 'update']);
        Route::delete('/{supplier}', [SupplierController::class, 'destroy']);
    });

    // Supplier Invoices (Fatture Passive)
    Route::prefix('supplier-invoices')->group(function () {
        Route::get('/', [SupplierInvoiceController::class, 'index']);
        Route::post('/', [SupplierInvoiceController::class, 'store']);
        Route::get('/export', [SupplierInvoiceController::class, 'export']);
        Route::get('/stats', [SupplierInvoiceController::class, 'stats']);
        Route::get('/upcoming', [SupplierInvoiceController::class, 'upcomingPayments']);
        Route::get('/overdue', [SupplierInvoiceController::class, 'overduePayments']);
        Route::post('/sync-fic', [SupplierInvoiceController::class, 'syncFromFIC']);
        Route::get('/{supplierInvoice}', [SupplierInvoiceController::class, 'show']);
        Route::put('/{supplierInvoice}', [SupplierInvoiceController::class, 'update']);
        Route::delete('/{supplierInvoice}', [SupplierInvoiceController::class, 'destroy']);
        Route::post('/{supplierInvoice}/mark-paid', [SupplierInvoiceController::class, 'markAsPaid']);
        Route::post('/{supplierInvoice}/upload-file', [SupplierInvoiceController::class, 'uploadFile']);
        Route::get('/{supplierInvoice}/download-file', [SupplierInvoiceController::class, 'downloadFile']);
    });
    
    // Sync route - Sincronizza tutto (protetto da auth)
    Route::post('/sync/all', [SyncController::class, 'syncAll']);

    // Test PostgreSQL connection
    Route::get('/sync/test-pgsql', [SyncController::class, 'testPostgreSQLConnection']);

    // Delivery Zones Management (PROTECTED)
    Route::prefix('delivery-zones')->group(function () {
        Route::get('/map', [DeliveryZoneController::class, 'mapZones']);
        Route::get('/debug-oppla', [DeliveryZoneController::class, 'debugOppla']);
        Route::post('/sync', [DeliveryZoneController::class, 'sync']); // Pull from OPPLA via Filament
        Route::post('/cleanup-restaurants', [DeliveryZoneController::class, 'cleanupRestaurantNames']);
        Route::post('/push-to-oppla', [DeliveryZoneController::class, 'pushToOppla']); // Push to OPPLA via Filament
        Route::get('/{id}', [DeliveryZoneController::class, 'show']);
        Route::post('/', [DeliveryZoneController::class, 'store']);
        Route::put('/{id}', [DeliveryZoneController::class, 'update']);
        Route::delete('/{id}', [DeliveryZoneController::class, 'destroy']);
    });

    // Email Automation CRM
    Route::prefix('crm/email-sequences')->group(function () {
        Route::get('/', [\App\Http\Controllers\EmailAutomationController::class, 'index']);
        Route::post('/', [\App\Http\Controllers\EmailAutomationController::class, 'store']);
        Route::get('/stats', [\App\Http\Controllers\EmailAutomationController::class, 'stats']);
        Route::get('/sent-emails', [\App\Http\Controllers\EmailAutomationController::class, 'sentEmails']);
        Route::get('/{emailSequence}', [\App\Http\Controllers\EmailAutomationController::class, 'show']);
        Route::put('/{emailSequence}', [\App\Http\Controllers\EmailAutomationController::class, 'update']);
        Route::delete('/{emailSequence}', [\App\Http\Controllers\EmailAutomationController::class, 'destroy']);
        Route::post('/{emailSequence}/activate', [\App\Http\Controllers\EmailAutomationController::class, 'activate']);
        Route::post('/{emailSequence}/pause', [\App\Http\Controllers\EmailAutomationController::class, 'pause']);
        Route::post('/{emailSequence}/enroll', [\App\Http\Controllers\EmailAutomationController::class, 'enroll']);
        Route::get('/{emailSequence}/enrollments', [\App\Http\Controllers\EmailAutomationController::class, 'enrollments']);
        
        // Steps management
        Route::post('/{emailSequence}/steps', [\App\Http\Controllers\EmailAutomationController::class, 'addStep']);
    });
    
    // Email Sequence Steps (standalone routes for editing/deleting steps)
    Route::prefix('crm/email-steps')->group(function () {
        Route::put('/{step}', [\App\Http\Controllers\EmailAutomationController::class, 'updateStep']);
        Route::delete('/{step}', [\App\Http\Controllers\EmailAutomationController::class, 'deleteStep']);
    });
    
    // Email Enrollments (standalone routes for pausing/resuming)
    Route::prefix('crm/email-enrollments')->group(function () {
        Route::post('/{enrollment}/pause', [\App\Http\Controllers\EmailAutomationController::class, 'pauseEnrollment']);
        Route::post('/{enrollment}/resume', [\App\Http\Controllers\EmailAutomationController::class, 'resumeEnrollment']);
    });

    // Financial Entries (Gestione Finanziaria)
    Route::prefix('financial-entries')->group(function () {
        Route::get('/summary', [\App\Http\Controllers\FinancialEntryController::class, 'summary']);
        Route::get('/', [\App\Http\Controllers\FinancialEntryController::class, 'index']);
        Route::post('/', [\App\Http\Controllers\FinancialEntryController::class, 'store']);
        Route::get('/{financialEntry}', [\App\Http\Controllers\FinancialEntryController::class, 'show']);
        Route::put('/{financialEntry}', [\App\Http\Controllers\FinancialEntryController::class, 'update']);
        Route::delete('/{financialEntry}', [\App\Http\Controllers\FinancialEntryController::class, 'destroy']);
    });

    // Cestino (Trash)
    Route::prefix('trash')->group(function () {
        Route::get('/', [\App\Http\Controllers\API\TrashController::class, 'index']);
        Route::delete('/empty', [\App\Http\Controllers\API\TrashController::class, 'empty']);
        Route::post('/{type}/{id}/restore', [\App\Http\Controllers\API\TrashController::class, 'restore']);
        Route::delete('/{type}/{id}', [\App\Http\Controllers\API\TrashController::class, 'forceDelete']);
    });

    // Cancellation (Annullamento unificato ordini/consegne)
    Route::prefix('cancel')->group(function () {
        Route::post('/preview', [CancellationController::class, 'preview']);
        Route::post('/execute', [CancellationController::class, 'execute']);
    });

    // Partner Report (preview & manual send)
    Route::get('/clients/{clientId}/monthly-report', [PartnerReportController::class, 'preview']);
    Route::post('/clients/{clientId}/monthly-report/send', [PartnerReportController::class, 'send']);

    // Appointment Recap Email
    Route::post('/clients/{clientId}/send-appointment-recap', function (\Illuminate\Http\Request $request, int $clientId) {
        $client = \App\Models\Client::findOrFail($clientId);

        if (!$client->email) {
            return response()->json(['message' => 'Il cliente non ha un indirizzo email configurato.'], 422);
        }

        $request->validate(['notes' => 'nullable|string|max:2000']);

        \Illuminate\Support\Facades\Mail::to($client->email)
            ->send(new \App\Mail\AppointmentRecapMail($client, $request->input('notes')));

        return response()->json([
            'message' => 'Email di recap appuntamento inviata con successo a ' . $client->email,
        ]);
    });

    // Partner Protection Module
    Route::prefix('partner-protection')->group(function () {
        // Incidents
        Route::get('/incidents', [PartnerProtectionController::class, 'listIncidents']);
        Route::get('/incidents/stats', [PartnerProtectionController::class, 'incidentStats']);
        Route::post('/incidents/delay', [PartnerProtectionController::class, 'reportDelay']);
        Route::post('/incidents/forgotten-item', [PartnerProtectionController::class, 'reportForgottenItem']);
        Route::post('/incidents/bulky-unmarked', [PartnerProtectionController::class, 'reportBulkyUnmarked']);
        Route::put('/incidents/{id}/resolve', [PartnerProtectionController::class, 'resolveIncident']);

        // Penalties
        Route::get('/penalties', [PartnerProtectionController::class, 'listPenalties']);
        Route::get('/penalties/preview', [PartnerProtectionController::class, 'previewPenaltyInvoices']);
        Route::post('/penalties/{id}/waive', [PartnerProtectionController::class, 'waivePenalty']);

        // Settings
        Route::get('/settings/{restaurantId?}', [PartnerProtectionController::class, 'getSettings']);
        Route::put('/settings/{restaurantId?}', [PartnerProtectionController::class, 'updateSettings']);

        // Restaurant Time Slots
        Route::get('/restaurants/{restaurantId}/time-slots', [PartnerProtectionController::class, 'getTimeSlots']);
        Route::put('/restaurants/{restaurantId}/time-slots', [PartnerProtectionController::class, 'updateTimeSlots']);
        Route::post('/restaurants/{restaurantId}/closure-override', [PartnerProtectionController::class, 'addClosureOverride']);

        // Restaurant Delivery Zones
        Route::get('/restaurants/{restaurantId}/delivery-zones', [PartnerProtectionController::class, 'getDeliveryZones']);
        Route::put('/restaurants/{restaurantId}/delivery-zones', [PartnerProtectionController::class, 'updateDeliveryZones']);

        // Order Validation
        Route::post('/validate-order', [PartnerProtectionController::class, 'validateOrder']);
    });
});
