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
    public $pdfContent;
    public $fileName;
    public $persona;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($nomina, $empresa, $pdfContent, $fileName)
    {
        $this->subject = "Detalles de tu nómina en {$empresa->nombre}";
        $this->nomina = $nomina;
        $this->empresa = $empresa;
        $this->pdfContent = $pdfContent;
        $this->fileName = $fileName;
        $this->persona = $nomina->persona;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        // Validar que el contenido del PDF no esté vacío
        if (empty($this->pdfContent)) {
            throw new \Exception('El contenido del PDF está vacío');
        }

        // Validar que el nombre del archivo sea válido
        if (empty($this->fileName) || !is_string($this->fileName)) {
            throw new \Exception('El nombre del archivo PDF no es válido');
        }

        return $this->subject($this->subject)
            ->from($this->empresa->email, $this->empresa->nombre)
            ->view('emails.nomina-emitida')
            ->attachData($this->pdfContent, $this->fileName, [
                'mime' => 'application/pdf',
            ]);
    }
}