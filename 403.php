<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acesso Negado - RapidJobs</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: linear-gradient(135deg, #FEE2E2, #FECACA);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .container {
            background: white;
            border-radius: 20px;
            padding: 60px 40px;
            text-align: center;
            max-width: 500px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.1);
        }
        .icon {
            width: 80px;
            height: 80px;
            background: #FEE2E2;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 24px;
        }
        .icon svg { color: #EF4444; }
        h1 { font-size: 2rem; color: #EF4444; margin-bottom: 12px; }
        p { color: #6B7280; margin-bottom: 24px; line-height: 1.6; }
        a {
            display: inline-block;
            background: #EF4444;
            color: white;
            padding: 12px 24px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
        }
        a:hover { background: #DC2626; }
    </style>
</head>
<body>
    <div class="container">
        <div class="icon">
            <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="12" cy="12" r="10"/>
                <line x1="4.93" y1="4.93" x2="19.07" y2="19.07"/>
            </svg>
        </div>
        <h1>403 - Acesso Negado</h1>
        <p>Você não tem permissão para acessar este arquivo ou diretório.</p>
        <a href="index.php">Voltar ao Início</a>
    </div>
</body>
</html>
