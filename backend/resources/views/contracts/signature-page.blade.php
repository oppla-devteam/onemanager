<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Firma Contratto - Opplà</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            max-width: 900px;
            width: 100%;
            overflow: hidden;
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 40px;
            text-align: center;
        }
        .header h1 {
            font-size: 28px;
            margin-bottom: 10px;
        }
        .header p {
            opacity: 0.9;
            font-size: 16px;
        }
        .content {
            padding: 40px;
        }
        .contract-info {
            background: #f8f9fa;
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 30px;
        }
        .info-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 1px solid #e0e0e0;
        }
        .info-row:last-child {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
        }
        .info-label {
            font-weight: 600;
            color: #555;
        }
        .info-value {
            color: #333;
            text-align: right;
        }
        .pdf-preview {
            border: 2px solid #e0e0e0;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 30px;
            text-align: center;
        }
        .pdf-preview iframe {
            width: 100%;
            height: 500px;
            border: none;
            border-radius: 8px;
        }
        .signature-section {
            background: #fff9e6;
            border: 2px dashed #ffc107;
            border-radius: 12px;
            padding: 30px;
            margin-bottom: 30px;
        }
        .signature-section h3 {
            color: #333;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .signature-section h3:before {
            content: "✍️";
            font-size: 24px;
        }
        .signature-canvas {
            border: 2px solid #ddd;
            border-radius: 8px;
            background: white;
            cursor: crosshair;
            display: block;
            margin: 0 auto;
        }
        .signature-controls {
            margin-top: 15px;
            display: flex;
            justify-content: center;
            gap: 10px;
        }
        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-weight: 600;
        }
        .btn-clear {
            background: #f44336;
            color: white;
        }
        .btn-clear:hover {
            background: #d32f2f;
            transform: translateY(-2px);
        }
        .otp-section {
            background: #e3f2fd;
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 30px;
        }
        .otp-section h3 {
            color: #1976d2;
            margin-bottom: 15px;
        }
        .otp-input {
            display: flex;
            gap: 10px;
            justify-content: center;
            margin-top: 15px;
        }
        .otp-input input {
            width: 50px;
            height: 60px;
            text-align: center;
            font-size: 24px;
            border: 2px solid #ddd;
            border-radius: 8px;
            font-weight: bold;
        }
        .otp-input input:focus {
            outline: none;
            border-color: #667eea;
        }
        .btn-request-otp {
            background: #2196f3;
            color: white;
            width: 100%;
            margin-top: 10px;
        }
        .btn-request-otp:hover {
            background: #1976d2;
        }
        .actions {
            display: flex;
            gap: 15px;
            margin-top: 30px;
        }
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            flex: 1;
            padding: 16px;
            font-size: 18px;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
        }
        .btn-primary:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        .btn-secondary {
            background: #f44336;
            color: white;
            padding: 16px 32px;
        }
        .btn-secondary:hover {
            background: #d32f2f;
            transform: translateY(-2px);
        }
        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .alert-info {
            background: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }
        @media (max-width: 768px) {
            .container {
                border-radius: 0;
            }
            .content {
                padding: 20px;
            }
            .signature-canvas {
                width: 100% !important;
                height: 200px !important;
            }
            .actions {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔐 Firma Elettronica del Contratto</h1>
            <p>Revisiona e firma digitalmente il tuo contratto con Opplà</p>
        </div>

        <div class="content">
            <div id="alertContainer"></div>

            <div class="contract-info">
                <div class="info-row">
                    <span class="info-label">Numero Contratto:</span>
                    <span class="info-value" id="contractNumber">Caricamento...</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Partner:</span>
                    <span class="info-value" id="partnerName">Caricamento...</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Data Inizio:</span>
                    <span class="info-value" id="startDate">Caricamento...</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Email:</span>
                    <span class="info-value" id="recipientEmail">Caricamento...</span>
                </div>
            </div>

            <div class="pdf-preview">
                <h3 style="margin-bottom: 20px; color: #333;">📄 Anteprima Contratto</h3>
                <iframe id="pdfFrame" src=""></iframe>
            </div>

            <div class="signature-section">
                <h3>Inserisci la tua firma</h3>
                <canvas id="signatureCanvas" class="signature-canvas" width="800" height="200"></canvas>
                <div class="signature-controls">
                    <button class="btn btn-clear" onclick="clearSignature()">🗑️ Cancella</button>
                </div>
            </div>

            <div class="otp-section">
                <h3>🔐 Verifica Identità (OTP)</h3>
                <p style="margin-bottom: 15px; color: #666;">
                    Per completare la firma, riceverai un codice OTP via email. 
                    Inserisci il codice a 6 cifre qui sotto.
                </p>
                <button class="btn btn-request-otp" onclick="requestOTP()">📧 Invia Codice OTP</button>
                <div class="otp-input">
                    <input type="text" maxlength="1" id="otp1" onkeyup="moveToNext(this, 'otp2')">
                    <input type="text" maxlength="1" id="otp2" onkeyup="moveToNext(this, 'otp3')">
                    <input type="text" maxlength="1" id="otp3" onkeyup="moveToNext(this, 'otp4')">
                    <input type="text" maxlength="1" id="otp4" onkeyup="moveToNext(this, 'otp5')">
                    <input type="text" maxlength="1" id="otp5" onkeyup="moveToNext(this, 'otp6')">
                    <input type="text" maxlength="1" id="otp6" onkeyup="moveToNext(this, null)">
                </div>
            </div>

            <div class="actions">
                <button class="btn btn-secondary" onclick="declineContract()">❌ Rifiuta</button>
                <button class="btn btn-primary" id="signButton" onclick="signContract()" disabled>
                    ✍️ Firma e Completa
                </button>
            </div>
        </div>
    </div>

    <script>
        const API_URL = 'http://localhost:8000/api';
        const token = window.location.pathname.split('/').pop();
        let canvas, ctx;
        let isDrawing = false;
        let hasSignature = false;

        // Initialize
        document.addEventListener('DOMContentLoaded', function() {
            canvas = document.getElementById('signatureCanvas');
            ctx = canvas.getContext('2d');
            setupCanvas();
            loadContractData();
        });

        function setupCanvas() {
            ctx.strokeStyle = '#000';
            ctx.lineWidth = 2;
            ctx.lineCap = 'round';

            canvas.addEventListener('mousedown', startDrawing);
            canvas.addEventListener('mousemove', draw);
            canvas.addEventListener('mouseup', stopDrawing);
            canvas.addEventListener('mouseout', stopDrawing);

            // Touch support
            canvas.addEventListener('touchstart', handleTouch);
            canvas.addEventListener('touchmove', handleTouch);
            canvas.addEventListener('touchend', stopDrawing);
        }

        function startDrawing(e) {
            isDrawing = true;
            const rect = canvas.getBoundingClientRect();
            ctx.beginPath();
            ctx.moveTo(e.clientX - rect.left, e.clientY - rect.top);
            hasSignature = true;
            checkFormComplete();
        }

        function draw(e) {
            if (!isDrawing) return;
            const rect = canvas.getBoundingClientRect();
            ctx.lineTo(e.clientX - rect.left, e.clientY - rect.top);
            ctx.stroke();
        }

        function stopDrawing() {
            isDrawing = false;
        }

        function handleTouch(e) {
            e.preventDefault();
            const touch = e.touches[0];
            const mouseEvent = new MouseEvent(
                e.type === 'touchstart' ? 'mousedown' : 
                e.type === 'touchmove' ? 'mousemove' : 'mouseup',
                {
                    clientX: touch.clientX,
                    clientY: touch.clientY
                }
            );
            canvas.dispatchEvent(mouseEvent);
        }

        function clearSignature() {
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            hasSignature = false;
            checkFormComplete();
        }

        function moveToNext(current, nextId) {
            if (current.value.length >= 1 && nextId) {
                document.getElementById(nextId).focus();
            }
            checkFormComplete();
        }

        function checkFormComplete() {
            const otp = getOTP();
            const signButton = document.getElementById('signButton');
            signButton.disabled = !(hasSignature && otp.length === 6);
        }

        function getOTP() {
            let otp = '';
            for (let i = 1; i <= 6; i++) {
                otp += document.getElementById(`otp${i}`).value;
            }
            return otp;
        }

        async function loadContractData() {
            try {
                const response = await fetch(`${API_URL}/contracts/sign/${token}`);
                const data = await response.json();

                if (!response.ok) {
                    showAlert(data.message || 'Contratto non trovato', 'error');
                    return;
                }

                document.getElementById('contractNumber').textContent = data.contract.contract_number;
                document.getElementById('partnerName').textContent = data.contract.client.ragione_sociale;
                document.getElementById('startDate').textContent = new Date(data.contract.start_date).toLocaleDateString('it-IT');
                document.getElementById('recipientEmail').textContent = data.signature.recipient_email;

                // Load PDF
                if (data.contract.pdf_path) {
                    document.getElementById('pdfFrame').src = `${API_URL.replace('/api', '')}/storage/${data.contract.pdf_path}`;
                }
            } catch (error) {
                showAlert('Errore nel caricamento del contratto', 'error');
                console.error(error);
            }
        }

        async function requestOTP() {
            try {
                showAlert('Invio OTP in corso...', 'info');
                const response = await fetch(`${API_URL}/contracts/sign/${token}/otp`, {
                    method: 'POST'
                });
                const data = await response.json();

                if (response.ok) {
                    showAlert('✅ Codice OTP inviato via email!', 'success');
                    document.getElementById('otp1').focus();
                } else {
                    showAlert(data.message || 'Errore nell\'invio OTP', 'error');
                }
            } catch (error) {
                showAlert('Errore nella richiesta OTP', 'error');
                console.error(error);
            }
        }

        async function signContract() {
            const otp = getOTP();
            if (!hasSignature) {
                showAlert('Inserisci la tua firma prima di procedere', 'error');
                return;
            }
            if (otp.length !== 6) {
                showAlert('Inserisci il codice OTP completo', 'error');
                return;
            }

            try {
                showAlert('Firma in corso...', 'info');
                const signatureData = canvas.toDataURL('image/png');

                const response = await fetch(`${API_URL}/contracts/sign/${token}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        otp_code: otp,
                        signature_data: signatureData
                    })
                });

                const data = await response.json();

                if (response.ok) {
                    showAlert('✅ Contratto firmato con successo!', 'success');
                    setTimeout(() => {
                        window.location.href = '/contracts/signed-success';
                    }, 2000);
                } else {
                    showAlert(data.message || 'Errore nella firma', 'error');
                }
            } catch (error) {
                showAlert('Errore nella firma del contratto', 'error');
                console.error(error);
            }
        }

        async function declineContract() {
            if (!confirm('Sei sicuro di voler rifiutare questo contratto?')) {
                return;
            }

            try {
                const response = await fetch(`${API_URL}/contracts/sign/${token}/decline`, {
                    method: 'POST'
                });
                const data = await response.json();

                if (response.ok) {
                    showAlert('Contratto rifiutato', 'info');
                    setTimeout(() => {
                        window.location.href = '/contracts/declined';
                    }, 2000);
                } else {
                    showAlert(data.message || 'Errore', 'error');
                }
            } catch (error) {
                showAlert('Errore nel rifiuto del contratto', 'error');
                console.error(error);
            }
        }

        function showAlert(message, type) {
            const container = document.getElementById('alertContainer');
            const alert = document.createElement('div');
            alert.className = `alert alert-${type}`;
            alert.textContent = message;
            container.innerHTML = '';
            container.appendChild(alert);

            if (type !== 'error') {
                setTimeout(() => {
                    alert.remove();
                }, 5000);
            }
        }
    </script>
</body>
</html>
