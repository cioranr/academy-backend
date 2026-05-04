<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Degree;
use App\Models\EventRegistration;
use App\Support\DiplomaFonts;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class DiplomaController extends Controller
{
    /**
     * Generate a diploma PDF for a registration and store it as a Degree row.
     */
    public function generate(Request $request, EventRegistration $registration): JsonResponse
    {
        if (! $request->user()?->isEventsManager()) {
            abort(403);
        }

        $event = $registration->event()->with('directors.doctor')->firstOrFail();

        // If a diploma already exists for this user + event, regenerate it (overwrite the file)
        // so admin re-clicks pick up template/data changes.
        $existing = Degree::where('user_id', $registration->user_id)
            ->where('event_id', $event->id)
            ->first();

        if ($existing && Storage::disk('public')->exists($existing->file_path)) {
            Storage::disk('public')->delete($existing->file_path);
        }

        $doctorName = trim($registration->first_name . ' ' . $registration->last_name);
        $location   = trim(implode(', ', array_filter([$event->location, $event->venue])));

        $start = Carbon::parse($event->date)->locale('ro');
        $end   = $event->end_date ? Carbon::parse($event->end_date)->locale('ro') : null;
        if ($end && $end->gt($start)) {
            $period = $start->isSameMonth($end)
                ? $start->day . '-' . $end->day . ' ' . $start->isoFormat('MMMM YYYY')
                : $start->isoFormat('D MMMM') . ' - ' . $end->isoFormat('D MMMM YYYY');
        } else {
            $period = $start->isoFormat('D MMMM YYYY');
        }

        $logoFile  = $event->credits ? 'logos/amici_logo.png' : 'logos/logo.png';
        $logoPath  = Storage::disk('public')->exists($logoFile)
            ? storage_path('app/public/' . $logoFile)
            : null;

        $signers = $event->directors->map(function ($d) {
            $sig = optional($d->doctor)->signature;
            $sigPath = null;
            if ($sig && str_starts_with($sig, '/storage/')) {
                $rel = ltrim(str_replace('/storage/', '', $sig), '/');
                if (Storage::disk('public')->exists($rel)) {
                    $sigPath = storage_path('app/public/' . $rel);
                }
            }
            return [
                'name'      => $d->name,
                'specialty' => $d->specialty,
                'signature' => $sigPath,
            ];
        })->values()->all();

        // Make sure dompdf has a writable cache dir for the .ufm font metric files it generates.
        $fontCache = storage_path('fonts');
        if (! is_dir($fontCache)) @mkdir($fontCache, 0775, true);

        $faces = DiplomaFonts::scanFaces();

        $pdf = Pdf::loadView('pdf.diploma', [
            'doctor_name'    => $doctorName,
            'workshop_title' => $event->title,
            'location'       => $location,
            'period'         => $period,
            'credits'        => $event->credits,
            'cmr_address'    => $event->cmr_address,
            'logo_path'      => $logoPath,
            'signers'        => $signers,
            'font_faces'     => $faces,
        ])->setPaper('a4', 'landscape')
          ->setOptions([
              'fontDir'       => $fontCache,
              'fontCache'     => $fontCache,
              'tempDir'       => $fontCache,
              'chroot'        => storage_path('app/public'),
              'isRemoteEnabled' => true,
          ]);

        $userId    = $registration->user_id;
        $slug      = $event->slug ?: 'event-' . $event->id;
        $fileName  = 'diploma-' . $slug . '-' . $registration->id . '.pdf';
        $storePath = "degrees/{$userId}/{$fileName}";

        Storage::disk('public')->put($storePath, $pdf->output());

        $payload = [
            'user_id'     => $userId,
            'event_id'    => $event->id,
            'title'       => 'Certificat de participare — ' . $event->title,
            'file_path'   => $storePath,
            'file_name'   => $fileName,
            'uploaded_by' => $request->user()->id,
        ];

        if ($existing) {
            $existing->update($payload);
            $degree = $existing->fresh();
        } else {
            $degree = Degree::create($payload);
        }

        return response()->json($degree->load(['event', 'uploader']), $existing ? 200 : 201);
    }
}
