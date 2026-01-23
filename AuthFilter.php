<?php
declare(strict_types=1);

Yii::import('application.components.inputProcessor.CredentialsInputProcessor');

/**
 * API key and domain authorization filter
 */
class AuthFilter extends CFilter
{
    /**
     * @var CredentialsDto|null Reference to DTO
     */
    public ?CredentialsDto $credentialsDto = null;

    /**
     * @param CFilterChain $filterChain
     * @return bool
     */
    protected function preFilter(CFilterChain $filterChain): bool
    {
        $this->credentialsDto = $this->getCredentials();

        $domain = $this->credentialsDto->domain ?? null;
        $token  = $this->credentialsDto->token ?? null;

        if ($domain === null || $token === null) {
            $this->logAttempt($domain, 'Missing credentials');
            $this->sendError(400, 'Missing credentials');
            return false;
        }

        $apiLogin = ApiLogin::model()->find('domain=:domain', [':domain' => $domain]);

        if ($apiLogin === null || !$this->verifyToken($token, $apiLogin->token_hash)) {
            $this->logAttempt($domain, 'Invalid login credentials');
            $this->sendError(401, 'Invalid login credentials');
            return false;
        }

        return true;
    }

    /**
     * Retrieve authorization data from Authorization header or JSON/POST
     */
    protected function getCredentials(): CredentialsDto
    {
        $domain = null;
        $token = null;

        // Get token from Authorization: Bearer <token> header
        $headers = getallheaders();
        if (!empty($headers['Authorization'])) {
            if (preg_match('/Bearer\s(\S+)/', $headers['Authorization'], $matches)) {
                $token = $matches[1];
            }
        }

        // Fallback to JSON/POST if header not present
        $input = json_decode((string)file_get_contents('php://input'), true);
        if (!is_array($input)) {
            $input = $_POST;
        }

        $this->credentialsDto = (new CredentialsInputProcessor())->inputProcess($input);

        // Header token takes priority
        if ($token !== null) {
            $this->credentialsDto->token = $token;
        }

        return $this->credentialsDto;
    }

    protected function verifyToken(string $token, string $tokenHash): bool
    {
        return hash_equals($tokenHash, hash_hmac('sha256', $token, (string)Yii::app()->params['apiSecretKey']));
    }

    protected function sendError(int $code, string $message): void
    {
        header("Content-Type: application/json");
        http_response_code($code);

        echo CJSON::encode([
            'id' => Yii::app()->controller->route,
            'result' => null,
            'error' => $message,
        ]);

        Yii::app()->end();
    }

    /**
     * Logging failed authorization attempts
     */
    protected function logAttempt(?string $domain, string $reason): void
    {
        Yii::log(
            sprintf(
                "API auth failed. Domain: %s, Reason: %s, IP: %s",
                $domain ?? 'N/A',
                $reason,
                $_SERVER['REMOTE_ADDR'] ?? 'unknown'
            ),
            CLogger::LEVEL_WARNING,
            'application.filters.AuthFilter'
        );
    }
}
