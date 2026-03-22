<p>Witaj {{ $appointment->patient->first_name }},</p>

<p>Przypominamy o Twojej jutrzejszej wizycie:</p>

<ul>
    <li><strong>Data:</strong> {{ $appointment->start->format('d.m.Y H:i') }}</li>
    <li><strong>Status:</strong> {{ $appointment->status->description() }}</li>
</ul>

<p>Pozdrawiamy,<br>Zespół kliniki</p>
