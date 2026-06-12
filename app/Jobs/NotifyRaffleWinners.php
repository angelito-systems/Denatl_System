<?php

namespace App\Jobs;

use App\Models\Raffle;
use App\Services\EvolutionApiService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class NotifyRaffleWinners implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $raffleId;

    public function __construct($raffleId)
    {
        $this->raffleId = $raffleId;
    }

    public function handle(EvolutionApiService $evolutionApi): void
    {
        $raffle = Raffle::with('winners.prize')->find($this->raffleId);
        if (!$raffle) {
            return;
        }

        foreach ($raffle->winners as $winner) {
            $msg = "🎉 *¡FELICIDADES!* 🎉\n\n";
            $msg .= "¡Has resultado ganador(a) en nuestro sorteo: *{$raffle->name}*!\n\n";
            
            if ($winner->prize) {
                $msg .= "🏆 Tu premio es: *{$winner->prize->name}*\n\n";
            }
            
            $msg .= "Por favor, comunícate con nosotros para coordinar la entrega de tu premio.\n";
            $msg .= "¡Gracias por participar y confiar en nosotros! 🦷💙";

            $evolutionApi->sendText($winner->phone_number, $msg);
            
            // Pausa breve para evitar limite de tasa (Rate Limit) de la API
            sleep(2);
        }
    }
}
