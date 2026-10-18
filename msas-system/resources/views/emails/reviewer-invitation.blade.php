<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>MSAS FarmAI review invitation</title></head>
<body style="margin:0;padding:0;background:#f1f5f9;font-family:'Segoe UI',Arial,sans-serif">
<div style="max-width:560px;margin:32px auto;background:#fff;border-radius:16px;overflow:hidden">
  <div style="background:#0F3460;padding:24px 32px;text-align:center">
    <h1 style="color:#fff;font-size:20px;margin:0">MSAS FarmAI</h1>
    <p style="color:#94a3b8;font-size:13px;margin:4px 0 0">{{ $language->name_english }} review &middot; {{ $kindLabel }}</p>
  </div>
  <div style="padding:28px 32px;font-size:14px;color:#334155;line-height:1.7">
    <p style="font-weight:600;color:#1e293b">Hello {{ $reviewer->first_name }},</p>
    <p>You are invited to check MSAS FarmAI scan results in <strong>{{ $language->name_english }}</strong>: read each result, listen to the voices, give scores and mark any mistakes. It takes about 10 minutes per result.</p>
    @if($setPasswordLink)
    <p>First, choose your own password. This link works for 7 days:</p>
    <p style="text-align:center"><a href="{{ $setPasswordLink }}" style="display:inline-block;background:#0F6B3E;color:#fff;text-decoration:none;font-weight:700;padding:12px 26px;border-radius:10px">Set my password</a></p>
    @else
    <p>Sign in with your existing MSAS FarmAI account.</p>
    @endif
    <p>Then open the review page: <a href="{{ route('admin.speech.review.index') }}">{{ route('admin.speech.review.index') }}</a></p>
    <p>The reviewer guide (English and Hausa) explains what to listen for and how to score.</p>
    <hr style="border:none;border-top:1px solid #e2e8f0;margin:20px 0">
    <p lang="ha"><strong>Hausa:</strong> Sannu {{ $reviewer->first_name }}. Muna gayyatarka ka duba sakamakon binciken MSAS FarmAI a {{ $language->name_native }}: ka karanta, ka saurari muryoyin, ka ba da maki, kuma ka nuna kurakurai.@if($setPasswordLink) Da farko ka zaɓi kalmar sirrinka da maɓallin da ke sama (yana aiki kwana 7).@endif Sannan ka buɗe shafin dubawa da ke sama.</p>
    <p style="font-size:12px;color:#94a3b8">MSAS staff will never ask for your password. If you did not expect this email, ignore it.</p>
  </div>
</div>
</body>
</html>
