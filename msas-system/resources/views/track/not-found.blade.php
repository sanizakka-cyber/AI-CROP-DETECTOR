<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Order Not Found — MSAS FarmAI</title>
    <link href="https://fonts.bunny.net/css?family=poppins:700,800&family=inter:400,600&display=swap" rel="stylesheet"/>
    <style>
        *{box-sizing:border-box}body{font-family:'Inter',sans-serif;background:#f1f5f9;margin:0;min-height:100vh;display:flex;align-items:center;justify-content:center;padding:24px;}
        .card{background:#fff;border-radius:20px;border:1px solid #e2e8f0;padding:40px 32px;text-align:center;max-width:400px;width:100%;box-shadow:0 4px 20px rgba(0,0,0,.06);}
        h1{font-family:'Poppins',sans-serif;font-size:22px;font-weight:800;color:#0f172a;margin:16px 0 8px;}
        p{color:#64748b;font-size:14px;margin:0 0 24px;}
        a{display:inline-block;padding:10px 24px;background:#0F6B3E;color:#fff;border-radius:10px;font-weight:700;font-size:14px;text-decoration:none;}
    </style>
</head>
<body>
    <div class="card">
        <div style="font-size:48px;">🔍</div>
        <h1>Order Not Found</h1>
        <p>We couldn't find an order with number <strong>{{ $orderNumber }}</strong>. Please check and try again.</p>
        <a href="https://ai.msasagro.com">Back to MSAS</a>
    </div>
</body>
</html>
