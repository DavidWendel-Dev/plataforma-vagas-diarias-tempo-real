<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Erro do Servidor - RapidJobs</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: linear-gradient(135deg, #FEF3C7, #FDE68A);
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
            background: #FEF3C7;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 24px;
        }
        .icon svg { color: #F59E0B; }
        h1 { font-size: 2rem; color: #F59E0B; margin-bottom: 12px; }
        p { color: #6B7280; margin-bottom: 24px; line-height: 1.6; }
        a {
            display: inline-block;
            background: #F59E0B;
            color: white;
            padding: 12px 24px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
        }
        a:hover { background: #D97706; }
    </style>
</head>
<body>
    <div class="container">
        <div class="icon">
            <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/>
                <line x1="12" y1="9" x2="12" y2="13"/>
                <line x1="12" y1="17" x2="12.01" y2="17"/>
            </svg>
        </div>
        <h1>500 - Erro do Servidor</h1>
        <p>Ocorreu um erro interno no servidor. Por favor, tente novamente mais tarde.</p>
        <a href="index.php">Voltar ao Início</a>
    </div>
</body>
</html>
