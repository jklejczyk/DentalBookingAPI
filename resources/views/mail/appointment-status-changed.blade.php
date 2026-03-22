<p>Witaj {{ $appointment->patient->first_name }},</p>

<p>Status Twojej wizyty uległ zmianie:</p>

<ul>
    <li><strong>Status:</strong> {{ $appointment->status->description() }}</li>
    <li><strong>Data:</strong> {{ $appointment->start->format('d.m.Y H:i') }}</li>
</ul>

<p>Pozdrawiamy,<br>Zespół kliniki</p>