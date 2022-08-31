<?php

namespace App\Commands;

use Illuminate\Support\Facades\Http;
use LaravelZero\Framework\Commands\Command;
use Symfony\Component\DomCrawler\Crawler;

class Token extends Command
{
    protected $signature = 'token {username} {password} {--ua=}';

    protected $description = '取得有效的登入 token';

    public function handle()
    {
        $ua = $this->option('ua') ?? 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/104.0.5112.102 Safari/537.36';

        $response = Http::withHeaders(['User-Agent' => $ua])
            ->withoutRedirecting()
            ->get('https://ithelp.ithome.com.tw/users/login');

        $xsrfToken = $response->cookies()->getCookieByName('XSRF-TOKEN')->getValue();
        $sessionId = $response->cookies()->getCookieByName('ithelp2016_desktop')->getValue();

        $redirect = $response->header('location');

        $this->line('OAuth 2.0 authz url: ' . $redirect, null, 'vvv');

        // Redirect to OAuth 2.0 authentication url
        $response = Http::withHeaders(['User-Agent' => $ua])
            ->withoutRedirecting()
            ->get($redirect);

        $memberXsrfToken = $response->cookies()->getCookieByName('XSRF-TOKEN')->getValue();
        $memberSessionId = $response->cookies()->getCookieByName('ithomemembercenter_session')->getValue();

        $redirect = $response->header('location');

        $this->line('Challenge url: ' . $redirect, null, 'vvv');

        // Redirect to challenge url
        $response = Http::withHeaders(['User-Agent' => $ua])
            ->withoutRedirecting()
            ->withCookies([
                'XSRF-TOKEN' => $memberXsrfToken,
                'ithomemembercenter_session' => $memberSessionId,
            ], 'member.ithome.com.tw')
            ->get($redirect);

        $memberXsrfToken = $response->cookies()->getCookieByName('XSRF-TOKEN')->getValue();
        $memberSessionId = $response->cookies()->getCookieByName('ithomemembercenter_session')->getValue();

        $crawler = (new Crawler($response->body()))->filter('input[name=_token]');

        $inputToken = $crawler->first()->attr('value');

        $this->line('input[name=_token] = ' . $inputToken, null, 'vvv');

        $response = Http::withHeaders(['User-Agent' => $ua])
            ->withCookies([
                'XSRF-TOKEN' => $memberXsrfToken,
                'ithomemembercenter_session' => $memberSessionId,
            ], 'member.ithome.com.tw')
            ->withoutRedirecting()
            ->post('https://member.ithome.com.tw/login', [
                '_token' => $inputToken,
                'account' => $this->argument('username'),
                'password' => $this->argument('password'),
            ]);

        $memberXsrfToken = $response->cookies()->getCookieByName('XSRF-TOKEN')->getValue();
        $memberSessionId = $response->cookies()->getCookieByName('ithomemembercenter_session')->getValue();

        $redirect = $response->header('location');

        $this->line('OAuth 2.0 authz url: ' . $redirect, null, 'vvv');

        // Redirect to OAuth 2.0 authz url
        $response = Http::withHeaders(['User-Agent' => $ua])
            ->withCookies([
                'XSRF-TOKEN' => $memberXsrfToken,
                'ithomemembercenter_session' => $memberSessionId,
            ], 'member.ithome.com.tw')
            ->withoutRedirecting()
            ->get($redirect);

        $redirect = $response->header('location');

        $this->line('Callback to Client url with code: ' . $redirect, null, 'vvv');

        // Redirect to Client
        $response = Http::withHeaders(['User-Agent' => $ua])
            ->withCookies([
                'XSRF-TOKEN' => $xsrfToken,
                'ithelp2016_desktop' => $sessionId,
            ], 'ithelp.ithome.com.tw')
            ->withoutRedirecting()
            ->get($redirect);

        $token = $response->cookies()->getCookieByName('_token')->getValue();
        $uid = $response->cookies()->getCookieByName('uid')->getValue();
        $xsrfToken = $response->cookies()->getCookieByName('XSRF-TOKEN')->getValue();
        $sessionId = $response->cookies()->getCookieByName('ithelp2016_desktop')->getValue();

        $this->line($token);
    }
}
