<?php
/**
 * Validazione del token Bearer per le chiamate API.
 *
 * Verifica che ogni richiesta contenga un header Authorization
 * con un Bearer token valido, confrontandolo con il token configurato.
 */

declare(strict_types=1);

namespace Api;

class Auth
{
    private string $validToken;

    public function __construct(string $token)
    {
        $this->validToken = $token;
    }

    /**
     * Verifica che il token Bearer nella richiesta sia valido.
     *
     * @return bool true se il token è valido
     */
    public function validateRequest(): bool
    {
        $header = $this->getAuthorizationHeader();

        if ($header === null) {
            return false;
        }

        // Estrae il token dall'header "Bearer {token}"
        if (!preg_match('/^Bearer\s+(.+)$/i', $header, $matches)) {
            return false;
        }

        return hash_equals($this->validToken, trim($matches[1]));
    }

    /**
     * Recupera l'header Authorization dalla richiesta.
     */
    private function getAuthorizationHeader(): ?string
    {
        // Metodo 1: header standard
        if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
            return $_SERVER['HTTP_AUTHORIZATION'];
        }

        // Metodo 2: Apache con mod_rewrite
        if (isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
            return $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
        }

        // Metodo 3: funzione apache_request_headers()
        if (function_exists('apache_request_headers')) {
            $headers = apache_request_headers();
            foreach ($headers as $key => $value) {
                if (strtolower($key) === 'authorization') {
                    return $value;
                }
            }
        }

        return null;
    }
}
