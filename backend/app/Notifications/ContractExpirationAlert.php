<?php

namespace App\Notifications;

use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Collection;

class ContractExpirationAlert extends Notification implements ShouldQueue
{
    use Queueable;

    protected Collection $urgent;
    protected Collection $soon;
    protected Collection $upcoming;

    /**
     * Create a new notification instance.
     */
    public function __construct(Collection $urgent, Collection $soon, Collection $upcoming)
    {
        $this->urgent = $urgent;
        $this->soon = $soon;
        $this->upcoming = $upcoming;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $totalCount = $this->urgent->count() + $this->soon->count() + $this->upcoming->count();
        
        $message = (new MailMessage)
            ->subject("⚠️ {$totalCount} Contratti in Scadenza - Azione Richiesta")
            ->greeting("Ciao {$notifiable->name},")
            ->line("Ecco il riepilogo dei contratti in scadenza che richiedono la tua attenzione:");

        // Urgenti (entro 7 giorni)
        if ($this->urgent->isNotEmpty()) {
            $message->line("### 🔴 URGENTI (entro 7 giorni): {$this->urgent->count()}");
            foreach ($this->urgent->take(5) as $contract) {
                $daysLeft = Carbon::today()->diffInDays(Carbon::parse($contract->end_date));
                $clientName = $contract->client?->ragione_sociale ?? 'N/A';
                $message->line("- **{$contract->title}** ({$clientName}) - Scade tra {$daysLeft} giorni");
            }
            if ($this->urgent->count() > 5) {
                $message->line("... e altri " . ($this->urgent->count() - 5));
            }
        }

        // A breve (8-14 giorni)
        if ($this->soon->isNotEmpty()) {
            $message->line("### 🟠 A BREVE (8-14 giorni): {$this->soon->count()}");
            foreach ($this->soon->take(5) as $contract) {
                $daysLeft = Carbon::today()->diffInDays(Carbon::parse($contract->end_date));
                $clientName = $contract->client?->ragione_sociale ?? 'N/A';
                $message->line("- **{$contract->title}** ({$clientName}) - Scade tra {$daysLeft} giorni");
            }
            if ($this->soon->count() > 5) {
                $message->line("... e altri " . ($this->soon->count() - 5));
            }
        }

        // In arrivo (15-30 giorni)
        if ($this->upcoming->isNotEmpty()) {
            $message->line("### 🟡 IN ARRIVO (15-30 giorni): {$this->upcoming->count()}");
            foreach ($this->upcoming->take(3) as $contract) {
                $daysLeft = Carbon::today()->diffInDays(Carbon::parse($contract->end_date));
                $clientName = $contract->client?->ragione_sociale ?? 'N/A';
                $message->line("- **{$contract->title}** ({$clientName}) - Scade tra {$daysLeft} giorni");
            }
            if ($this->upcoming->count() > 3) {
                $message->line("... e altri " . ($this->upcoming->count() - 3));
            }
        }

        return $message
            ->action('Gestisci Contratti', config('app.url') . '/contracts')
            ->line('Contatta i clienti per valutare il rinnovo dei contratti in scadenza.')
            ->salutation('Il team OPPLA One Manager');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'contract_expiration_alert',
            'urgent_count' => $this->urgent->count(),
            'soon_count' => $this->soon->count(),
            'upcoming_count' => $this->upcoming->count(),
            'total_count' => $this->urgent->count() + $this->soon->count() + $this->upcoming->count(),
            'urgent_contracts' => $this->urgent->map(fn($c) => [
                'id' => $c->id,
                'title' => $c->title,
                'client' => $c->client?->ragione_sociale,
                'end_date' => $c->end_date,
            ])->toArray(),
            'message' => "Hai {$this->urgent->count()} contratti urgenti e " . 
                        ($this->soon->count() + $this->upcoming->count()) . " in scadenza",
        ];
    }
}
