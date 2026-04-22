<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PayMonitor Update Available</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background-color: #0d1117; color: #e2e8f0; line-height: 1.6; }
        .wrapper { max-width: 600px; margin: 0 auto; padding: 40px 20px; }
        .card { background-color: #161b22; border: 1px solid #21262d; border-radius: 16px; overflow: hidden; }
        .header { background: linear-gradient(135deg, #0d1117 0%, #161b22 100%); border-bottom: 1px solid #21262d; padding: 32px 36px; text-align: center; }
        .logo { font-size: 13px; font-weight: 700; letter-spacing: 0.2em; text-transform: uppercase; color: #8b949e; margin-bottom: 20px; }
        .header h1 { font-size: 24px; font-weight: 700; color: #ffffff; margin-bottom: 8px; }
        .header p { font-size: 14px; color: #8b949e; }
        .body { padding: 32px 36px; }
        .greeting { font-size: 15px; color: #e2e8f0; margin-bottom: 16px; }
        .intro { font-size: 14px; color: #8b949e; margin-bottom: 28px; line-height: 1.7; }
        .version-badge { background-color: #0d1117; border: 1px solid #22c55e; border-radius: 12px; padding: 20px; text-align: center; margin-bottom: 28px; }
        .version-badge .label { font-size: 11px; font-weight: 600; letter-spacing: 0.16em; text-transform: uppercase; color: #8b949e; margin-bottom: 8px; }
        .version-badge .version { font-size: 36px; font-weight: 800; color: #22c55e; font-family: 'Courier New', monospace; letter-spacing: -0.02em; }
        .version-badge .release-name { font-size: 13px; color: #8b949e; margin-top: 6px; }
        .section-title { font-size: 11px; font-weight: 600; letter-spacing: 0.16em; text-transform: uppercase; color: #8b949e; margin-bottom: 12px; padding-bottom: 8px; border-bottom: 1px solid #21262d; }
        .changelog { background-color: #0d1117; border: 1px solid #21262d; border-radius: 10px; padding: 16px 20px; margin-bottom: 28px; }
        .changelog ul { list-style: none; padding: 0; }
        .changelog ul li { font-size: 13px; color: #c9d1d9; padding: 5px 0; display: flex; align-items: flex-start; gap: 10px; }
        .changelog ul li::before { content: '•'; color: #22c55e; font-weight: bold; margin-top: 1px; flex-shrink: 0; }
        .no-changelog { font-size: 13px; color: #8b949e; font-style: italic; }
        .cta-section { margin-bottom: 28px; }
        .btn-primary { display: block; width: 100%; background-color: #22c55e; color: #000000; text-align: center; padding: 14px 24px; border-radius: 10px; font-size: 14px; font-weight: 700; text-decoration: none; margin-bottom: 10px; }
        .btn-secondary { display: block; width: 100%; background-color: transparent; color: #8b949e; text-align: center; padding: 12px 24px; border-radius: 10px; font-size: 13px; font-weight: 500; text-decoration: none; border: 1px solid #21262d; }
        .note { background-color: #0d1117; border-left: 3px solid #21262d; border-radius: 0 8px 8px 0; padding: 12px 16px; margin-bottom: 20px; font-size: 13px; color: #8b949e; line-height: 1.6; }
        .footer { padding: 24px 36px; border-top: 1px solid #21262d; text-align: center; }
        .footer p { font-size: 12px; color: #52525b; line-height: 1.6; }
        .footer .brand { font-weight: 700; color: #8b949e; }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="card">
            <div class="header">
                <div class="logo">PayMonitor</div>
                <h1>🚀 Update Available</h1>
                <p>A new version of PayMonitor is ready for your cooperative portal</p>
            </div>

            <div class="body">
                <p class="greeting">Hello <strong>{{ $adminName }}</strong>,</p>
                <p class="intro">
                    Good news! A new version of <strong>PayMonitor</strong> has been released and is now available for your cooperative portal.
                    This update includes improvements and fixes to help your team work more efficiently.
                </p>

                <div class="version-badge">
                    <div class="label">New Version Available</div>
                    <div class="version">{{ $latestVersion }}</div>
                    <div class="release-name">{{ $releaseName }}</div>
                </div>

                @php
                    $changelogLines = array_values(array_filter(
                        explode("\n", str_replace("\r\n", "\n", $changelog)),
                        fn($line) => trim($line) !== ''
                    ));
                    $bulletItems = array_values(array_filter($changelogLines, fn($line) => preg_match('/^[-*]\s+/', trim($line))));
                    $items = count($bulletItems) > 0 ? array_map(fn($l) => trim(preg_replace('/^[-*]\s+/', '', trim($l))), $bulletItems) : array_slice($changelogLines, 0, 8);
                @endphp

                @if(count($items) > 0)
                <div class="section-title">What's New</div>
                <div class="changelog">
                    <ul>
                        @foreach($items as $item)
                            <li>{{ $item }}</li>
                        @endforeach
                    </ul>
                </div>
                @else
                <div class="changelog">
                    <p class="no-changelog">No detailed changelog available for this release.</p>
                </div>
                @endif

                <div class="cta-section">
                    <a href="{{ $updatesUrl }}" class="btn-primary">Update My Portal Now →</a>
                    <a href="https://github.com/ToffDarell/PayMonitor/releases/latest" class="btn-secondary">View Full Changelog on GitHub</a>
                </div>

                <div class="note">
                    <strong>Need help?</strong> If you have questions about this update or run into any issues,
                    please contact your PayMonitor support team. Our team is ready to assist you.
                </div>
            </div>

            <div class="footer">
                <p>
                    This email was sent by <span class="brand">PayMonitor</span> to {{ $tenant->email }}<br>
                    You're receiving this because you are an administrator of <strong>{{ $tenant->name }}</strong>.<br>
                    <br>
                    © {{ date('Y') }} PayMonitor. All rights reserved.
                </p>
            </div>
        </div>
    </div>
</body>
</html>
