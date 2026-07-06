<?php

declare(strict_types=1);

namespace Shimmie2;

use function MicroHTML\{DIV, SCRIPT, emptyHTML};

use MicroHTML\HTMLElement;

final class Vulpstile extends CaptchaExtension
{
    public const KEY = "vulpstile";
    private const SITEVERIFY_URL = "https://challenges.cloudflare.com/turnstile/v0/siteverify";

    public function captcha_html(): HTMLElement|null
    {
        $captcha = null;
        $r_publickey = Ctx::$config->get(VulpstileConfig::VULPSTILE_PUBKEY);
        if (!empty($r_publickey)) {
            $captcha = emptyHTML(
                DIV(["class" => "cf-turnstile", "data-sitekey" => $r_publickey]),
                SCRIPT([
                    "type" => "text/javascript",
                    "src" => "https://challenges.cloudflare.com/turnstile/v0/api.js",
                    "async" => "async",
                    "defer" => "defer",
                ])
            );
        }
        return $captcha;
    }

    public function check_captcha(): bool
    {
        $r_privatekey = Ctx::$config->get(VulpstileConfig::VULPSTILE_PRIVKEY);
        if (!empty($r_privatekey)) {
            $token = $_POST['cf-turnstile-response'] ?? "";
            $resp = $this->verify_turnstile(
                $r_privatekey,
                is_string($token) ? $token : "",
                (string)Network::get_real_ip(),
            );

            if (($resp["success"] ?? false) !== true) {
                $errors = $resp["error-codes"] ?? ["unknown-error"];
                Log::info("vulpstile", "Captcha failed: " . implode("", $errors));
                return false;
            }
        }

        return true;
    }

    /**
     * @return array{success?: bool, error-codes?: string[]}
     */
    private function verify_turnstile(string $secret, string $response, string $remote_ip): array
    {
        if (empty($response)) {
            return ["success" => false, "error-codes" => ["missing-input-response"]];
        }

        if (!function_exists("curl_init")) {
            return ["success" => false, "error-codes" => ["missing-curl"]];
        }

        $ch = curl_init(self::SITEVERIFY_URL);
        if ($ch === false) {
            return ["success" => false, "error-codes" => ["internal-error"]];
        }

        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
            "secret" => $secret,
            "response" => $response,
            "remoteip" => $remote_ip,
        ]));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);

        $body = curl_exec($ch);
        $curl_error = curl_error($ch);
        curl_close($ch);

        if (!is_string($body)) {
            Log::info("vulpstile", "Captcha verification request failed: " . $curl_error);
            return ["success" => false, "error-codes" => ["internal-error"]];
        }

        $result = json_decode($body, true);
        if (!is_array($result)) {
            return ["success" => false, "error-codes" => ["invalid-json-response"]];
        }

        return $result;
    }
}
