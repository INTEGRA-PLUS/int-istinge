<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class NominaEmitida extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $subject;
    public $nomina;
    public $empresa;
    public $pdf;
    public $persona;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($nomina, $empresa, $pdf)
    {
        $this->subject = "Detalles de tu nómina en {$empresa->nombre}";
        $this->nomina = $nomina;
        $this->empresa = $empresa;
        $this->pdf = $pdf; // aquí ya llega la ruta relativa: empresa{id}/nominas/reporte/archivo.pdf
        $this->persona = $nomina->persona;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject($this->subject)
            ->from($this->empresa->email, $this->empresa->nombre)
            ->view('emails.nomina-emitida')
            
            ->attachFromStorageDisk('public', $this->pdf);
    }
}
