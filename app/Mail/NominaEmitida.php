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
    public $pdf; // aquí guardamos la ruta relativa dentro del disco "public"
    public $persona;

    public function __construct($nomina, $empresa, $pdf)
    {
        $this->subject = "Detalles de tu nómina en {$empresa->nombre}";
        $this->nomina = $nomina;
        $this->empresa = $empresa;
        $this->pdf = $pdf; // ejemplo: empresa5/nominas/reporte/nomina-1234.pdf
        $this->persona = $nomina->persona;
    }

    public function build()
    {
        return $this->subject($this->subject)
            ->from($this->empresa->email ?? config('mail.from.address'), $this->empresa->nombre)
            ->view('emails.nomina-emitida')
            // Aquí adjuntamos desde el disco "public" usando la ruta relativa
            ->attachFromStorageDisk('public', $this->pdf, [
                'as'   => "nomina-{$this->persona->nro_documento}.pdf",
                'mime' => 'application/pdf',
            ]);
    }
}
