$ErrorActionPreference = 'Stop'
$base = 'http://localhost:8080'
$suffix = Get-Random -Minimum 1000 -Maximum 9999
$stamp = Get-Date -Format 'HHmmss'
$emailFoto = "foto_$($stamp)_$suffix@test.dev"
$emailCli = "cliente_$($stamp)_$suffix@test.dev"
$password = "Passw0rd!123"

function Invoke-Json($method, $path, $body = $null, $cookie = $null) {
    $params = @{
        Uri             = "$base$path"
        Method          = $method
        UseBasicParsing = $true
    }
    # PS 5.1 descarta silenciosamente la cabecera Cookie pasada via -Headers;
    # hay que usar -WebSession (o -WebSession con Cookies.Add) para enviarla.
    $session = $null
    if ($cookie) {
        $session = New-Object Microsoft.PowerShell.Commands.WebRequestSession
        $netCookie = New-Object System.Net.Cookie(
            'cipher_forge_token',
            ($cookie -replace '^cipher_forge_token=', ''),
            '/',
            'localhost'
        )
        $session.Cookies.Add($netCookie)
        $params['WebSession'] = $session
    }
    if ($body) {
        $params['ContentType'] = 'application/json'
        $params['Body'] = ($body | ConvertTo-Json -Depth 6)
    }
    try {
        $r = Invoke-WebRequest @params
        $content = $r.Content
        # Captura la cookie de sesion (JWT + Cookies) devuelta por el login
        # para reenviarla en las siguientes peticiones (equivalente al
        # Bearer anterior; el token ya no viaja en el cuerpo JSON).
        # Se usa el indexer (no GetValues) porque GetValues lanza excepcion
        # en respuestas sin Set-Cookie (p. ej. el registro).
        $cookieSesion = $null
        $setCookies = $r.Headers['Set-Cookie']
        if ($setCookies) {
            $primera = @($setCookies) | Select-Object -First 1
            if ($primera -match '^([^;]+)') { $cookieSesion = $Matches[1] }
        }
        if ($content) { return [pscustomobject]@{ Status = $r.StatusCode; Json = ($content | ConvertFrom-Json); Cookie = $cookieSesion } }
        return [pscustomobject]@{ Status = $r.StatusCode; Json = $null; Cookie = $cookieSesion }
    } catch {
        $resp = $_.Exception.Response
        if (-not $resp) {
            return [pscustomobject]@{ Status = 0; Json = $null; Error = $_.Exception.Message }
        }
        $code = [int]$resp.StatusCode
        $stream = $resp.GetResponseStream()
        $reader = New-Object System.IO.StreamReader($stream)
        $txt = $reader.ReadToEnd()
        if ($txt) {
            try { return [pscustomobject]@{ Status = $code; Json = ($txt | ConvertFrom-Json) } }
            catch { return [pscustomobject]@{ Status = $code; Json = $null; Raw = $txt } }
        }
        return [pscustomobject]@{ Status = $code; Json = $null }
    }
}

function Get-CodigoVerificacion($email) {
    $prev = $ErrorActionPreference
    $ErrorActionPreference = 'Continue'
    $out = & docker exec cipher_forge_db mysql -ucipher_user -pcipher_password cipher_forge -N -s -e "SELECT codigo_verificacion FROM usuarios WHERE email='$email' ORDER BY id DESC LIMIT 1" 2>$null
    $ErrorActionPreference = $prev
    return ($out | Where-Object { $_ -match '^\d' } | Select-Object -Last 1).Trim()
}

Write-Output "== 1. Registro fotografo =="
$regFoto = Invoke-Json 'POST' '/auth/register' @{ nombre_completo='Foto Test'; email=$emailFoto; password=$password; rol='fotografo'; terminos_aceptados=$true }
Write-Output "  status=$($regFoto.Status)"
if ($regFoto.Json) { Write-Output "  ok=$($regFoto.Json.ok) mensaje=$($regFoto.Json.mensaje)" }

Write-Output "== 2. Verificar email fotografo =="
$cod = Get-CodigoVerificacion $emailFoto
Write-Output "  codigo=$cod"
$verFoto = Invoke-Json 'POST' '/auth/verificar-email' @{ email=$emailFoto; codigo=$cod }
Write-Output "  status=$($verFoto.Status) ok=$($verFoto.Json.ok)"

Write-Output "== 3. Login fotografo =="
$logFoto = Invoke-Json 'POST' '/auth/login' @{ email=$emailFoto; password=$password }
$cookieFoto = $logFoto.Cookie
Write-Output "  status=$($logFoto.Status) cookie=$(if($cookieFoto){'OK'}else{'NULL'})"

Write-Output "== 4. GET /fotografos/cuota (fotografo, espera 200) =="
$cuota = Invoke-Json 'GET' '/fotografos/cuota' $null $cookieFoto
Write-Output "  status=$($cuota.Status)"
if ($cuota.Json) { Write-Output "  ok=$($cuota.Json.ok) mensaje=$($cuota.Json.mensaje)" }

Write-Output "== 5. Registro cliente =="
$regCli = Invoke-Json 'POST' '/auth/register' @{ nombre_completo='Cliente Test'; email=$emailCli; password=$password; rol='cliente'; terminos_aceptados=$true }
Write-Output "  status=$($regCli.Status)"

Write-Output "== 6. Verificar email cliente =="
$codC = Get-CodigoVerificacion $emailCli
$verCli = Invoke-Json 'POST' '/auth/verificar-email' @{ email=$emailCli; codigo=$codC }
Write-Output "  status=$($verCli.Status) ok=$($verCli.Json.ok)"

Write-Output "== 7. Login cliente =="
$logCli = Invoke-Json 'POST' '/auth/login' @{ email=$emailCli; password=$password }
$cookieCli = $logCli.Cookie
Write-Output "  status=$($logCli.Status) cookie=$(if($cookieCli){'OK'}else{'NULL'})"

Write-Output "== 8. GET /fotografos/cuota (cliente, espera 403) =="
$cuotaCli = Invoke-Json 'GET' '/fotografos/cuota' $null $cookieCli
Write-Output "  status=$($cuotaCli.Status)"
if ($cuotaCli.Json) { Write-Output "  ok=$($cuotaCli.Json.ok) mensaje=$($cuotaCli.Json.mensaje)" }

Write-Output "== 9. GET /fotografos/cuota (sin cookie, espera 401) =="
$cuotaNo = Invoke-Json 'GET' '/fotografos/cuota' $null $null
Write-Output "  status=$($cuotaNo.Status)"
if ($cuotaNo.Json) { Write-Output "  ok=$($cuotaNo.Json.ok) mensaje=$($cuotaNo.Json.mensaje)" }

Write-Output "== 10. GET /fotografos/1 perfil (publico, espera 200) =="
$perf = Invoke-Json 'GET' '/fotografos/1' $null $null
Write-Output "  status=$($perf.Status)"

Write-Output "== 11. Logout fotografo (espera 200 + Set-Cookie deleted) =="
$logout = Invoke-Json 'POST' '/auth/logout' $null $cookieFoto
Write-Output "  status=$($logout.Status)"
if ($logout.Json) { Write-Output "  ok=$($logout.Json.ok) mensaje=$($logout.Json.mensaje)" }
# El JWT es stateless: el backend no invalida el token en el servidor, solo
# borra la cookie en el navegador (Set-Cookie deleted + Max-Age=0). Verificamos
# que el header de borrado venga presente en la respuesta del logout.
$logoutBorrado = ($logout.Cookie -eq 'cipher_forge_token=deleted') -or ($logout.Cookie -match 'deleted')
Write-Output "  set-cookie-borrada=$(if($logoutBorrado){'OK'}else{'NO'})"