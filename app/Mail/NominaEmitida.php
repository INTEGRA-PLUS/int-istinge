<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Model\Nomina\Nomina;

class NominaEmitida extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $subject;
    public $nominaId;
    public $empresaData;
    public $pdfContent;
    public $fileName;
    public $personaData;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($nomina, $empresa, $pdfContent, $fileName)
    {
        $this->nominaId = $nomina->id;
        $this->empresaData = [
            'nombre' => $empresa->nombre,
            'email' => $empresa->email ?? config('mail.from.address'),
        ];
        $this->personaData = [
            'nombre' => $nomina->persona->nombre,
            'apellido' => $nomina->persona->apellido,
            'correo' => $nomina->persona->correo,
        ];
        $this->subject = "Detalles de tu nómina en {$empresa->nombre}";
        $this->pdfContent = $pdfContent;
        $this->fileName = $fileName;
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

        // Reconstruir los objetos para la vista si es necesario
        $nomina = Nomina::find($this->nominaId);

        return $this->subject($this->subject)
            ->from($this->empresaData['email'], $this->empresaData['nombre'])
            ->view('emails.nomina-emitida')
            ->with([
                'nomina' => $nomina,
                'empresa' => (object) $this->empresaData,
                'persona' => (object) $this->personaData
            ])
            ->attachData($this->pdfContent, $this->fileName, [
                'mime' => 'application/pdf',
            ]);
    }
}