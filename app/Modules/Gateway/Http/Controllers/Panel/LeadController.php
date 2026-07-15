<?php

namespace App\Modules\Gateway\Http\Controllers\Panel;

use App\Modules\Gateway\Http\Controllers\Controller;
use App\Modules\Gateway\Models\Lead;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LeadController extends Controller
{
    public function index()
    {
        $leads = Lead::orderByDesc('created_at')->paginate(50)
            ->through(fn (Lead $lead) => [
                'id' => $lead->id,
                'created_at' => $lead->created_at?->format('d.m.Y H:i'),
                'name' => $lead->name,
                'email' => $lead->email,
                'phone' => $lead->phone,
                'company' => $lead->company,
                'message' => $lead->message,
            ]);

        return Inertia::render('Gateway/Leads', [
            'leads' => $leads,
            'exportUrl' => route('panel.leads.export'),
        ]);
    }

    public function exportCsv(): StreamedResponse
    {
        $filename = 'leady_' . now()->format('Y-m-d_His') . '.csv';

        return response()->streamDownload(function () {
            $out = fopen('php://output', 'w');
            // BOM dla poprawnych polskich znaków w Excelu
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['Data', 'Imię i nazwisko', 'E-mail', 'Telefon', 'Firma', 'Wiadomość'], ';');

            Lead::orderByDesc('created_at')->chunk(200, function ($leads) use ($out) {
                foreach ($leads as $lead) {
                    fputcsv($out, [
                        $lead->created_at?->format('Y-m-d H:i'),
                        $lead->name,
                        $lead->email,
                        $lead->phone,
                        $lead->company,
                        $lead->message,
                    ], ';');
                }
            });

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
