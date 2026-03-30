<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Carbon\Carbon;

class RestaurantTimeSlot extends Model
{
    use HasFactory;

    protected $table = 'restaurant_time_slots';

    const SLOT_LUNCH = 'lunch';
    const SLOT_DINNER = 'dinner';
    const SLOT_ALL_DAY = 'all_day';
    const SLOT_CUSTOM = 'custom';

    protected $fillable = [
        'restaurant_id',
        'day_of_week',
        'slot_type',
        'start_time',
        'end_time',
        'is_active',
        'override_date',
        'is_closed_override',
    ];

    protected $casts = [
        'day_of_week' => 'integer',
        'is_active' => 'boolean',
        'override_date' => 'date',
        'is_closed_override' => 'boolean',
    ];

    public function restaurant()
    {
        return $this->belongsTo(Restaurant::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForDay($query, int $dayOfWeek)
    {
        return $query->where(function ($q) use ($dayOfWeek) {
            $q->where('day_of_week', $dayOfWeek)
              ->orWhereNull('day_of_week');
        });
    }

    public function scopeForDate($query, Carbon $date)
    {
        return $query->where('override_date', $date->toDateString());
    }

    /**
     * Verifica se un orario è all'interno di questo slot
     */
    public function containsTime(string $time): bool
    {
        $checkTime = Carbon::createFromTimeString($time);
        $start = Carbon::createFromTimeString($this->start_time);
        $end = Carbon::createFromTimeString($this->end_time);

        // Gestione slot che attraversano la mezzanotte
        if ($end->lessThan($start)) {
            return $checkTime->greaterThanOrEqualTo($start) || $checkTime->lessThan($end);
        }

        return $checkTime->greaterThanOrEqualTo($start) && $checkTime->lessThan($end);
    }

    /**
     * Verifica se il ristorante accetta ordini in un determinato momento
     */
    public static function isRestaurantOpen(int $restaurantId, ?Carbon $dateTime = null): bool
    {
        $dateTime = $dateTime ?? Carbon::now();
        $date = $dateTime->copy()->startOfDay();
        $dayOfWeek = $dateTime->dayOfWeek;
        $time = $dateTime->format('H:i:s');

        // Check per override della data specifica (chiusura straordinaria)
        $override = self::where('restaurant_id', $restaurantId)
            ->where('override_date', $date->toDateString())
            ->first();

        if ($override) {
            if ($override->is_closed_override) {
                return false;
            }
            // Se c'è un override non di chiusura, usa quell'orario
            return $override->is_active && $override->containsTime($time);
        }

        // Check normale per giorno della settimana
        $slots = self::where('restaurant_id', $restaurantId)
            ->where('is_active', true)
            ->whereNull('override_date')
            ->where(function ($q) use ($dayOfWeek) {
                $q->where('day_of_week', $dayOfWeek)
                  ->orWhereNull('day_of_week');
            })
            ->get();

        foreach ($slots as $slot) {
            if ($slot->containsTime($time)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Ottiene gli slot attivi per un ristorante in un giorno
     */
    public static function getSlotsForRestaurantDay(int $restaurantId, ?Carbon $date = null): array
    {
        $date = $date ?? Carbon::today();
        $dayOfWeek = $date->dayOfWeek;

        // Prima controlla gli override
        $override = self::where('restaurant_id', $restaurantId)
            ->where('override_date', $date->toDateString())
            ->get();

        if ($override->isNotEmpty()) {
            return $override->toArray();
        }

        // Altrimenti ritorna gli slot normali
        return self::where('restaurant_id', $restaurantId)
            ->where('is_active', true)
            ->whereNull('override_date')
            ->where(function ($q) use ($dayOfWeek) {
                $q->where('day_of_week', $dayOfWeek)
                  ->orWhereNull('day_of_week');
            })
            ->get()
            ->toArray();
    }

    public static function getDayLabel(int $dayOfWeek): string
    {
        return match ($dayOfWeek) {
            0 => 'Domenica',
            1 => 'Lunedì',
            2 => 'Martedì',
            3 => 'Mercoledì',
            4 => 'Giovedì',
            5 => 'Venerdì',
            6 => 'Sabato',
            default => '',
        };
    }

    public static function getSlotTypeLabel(string $type): string
    {
        return match ($type) {
            self::SLOT_LUNCH => 'Pranzo',
            self::SLOT_DINNER => 'Cena',
            self::SLOT_ALL_DAY => 'Tutto il giorno',
            self::SLOT_CUSTOM => 'Personalizzato',
            default => $type,
        };
    }
}
