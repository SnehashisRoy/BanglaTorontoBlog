<?php

namespace App\Services;

use App\Dto\CjProductDetail;
use App\Dto\CjProductSummary;
use App\Dto\CjSearchResult;
use App\Dto\CjTokenPair;
use App\Models\VendorCjCredential;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

/**
 * Thin wrapper over CJ Dropshipping's Open API (developers.cjdropshipping.com).
 * Initially built against their published docs (2026-10-03):
 *   - auth.html            → getAccessToken / refreshAccessToken
 *   - start/token.html     → CJ-Access-Token header on authenticated calls
 *   - api2/api/product.html → product/listV2, product/query
 *
 * Verified against a real connected account on 2026-10-04, which surfaced two
 * places the real API doesn't match CJ's own docs — see search()'s nesting
 * note and CjVariant's stock-unknown note. Order placement is still
 * unverified (out of scope — this build never calls CJ's order endpoints).
 */
class CjDropshippingClient
{
    private readonly string $baseUrl;

    public function __construct(?string $baseUrl = null)
    {
        $this->baseUrl = $baseUrl ?? (string) config('services.cj_dropshipping.base_url');
    }

    /**
     * Exchange a vendor's self-generated CJ API key for a token pair.
     * The raw key is used only for this one call — callers must not store it.
     */
    public function connect(string $apiKey): CjTokenPair
    {
        $response = $this->request()->post('/authentication/getAccessToken', [
            'apiKey' => $apiKey,
        ]);

        return CjTokenPair::fromArray($this->unwrap($response));
    }

    public function refresh(string $refreshToken): CjTokenPair
    {
        $response = $this->request()->post('/authentication/refreshAccessToken', [
            'refreshToken' => $refreshToken,
        ]);

        return CjTokenPair::fromArray($this->unwrap($response));
    }

    /**
     * A valid access token for this vendor, transparently refreshing (and
     * persisting the new pair) first if the current one is close to expiring.
     */
    public function accessTokenFor(VendorCjCredential $credential): string
    {
        if ($credential->accessTokenIsExpiring()) {
            $pair = $this->refresh($credential->refresh_token);

            $credential->update([
                'access_token' => $pair->accessToken,
                'refresh_token' => $pair->refreshToken,
                'access_token_expires_at' => $pair->accessTokenExpiresAt,
                'refresh_token_expires_at' => $pair->refreshTokenExpiresAt,
            ]);
        }

        return $credential->access_token;
    }

    /**
     * CJ's own documented maximum for `size` — requesting more than this
     * returns an error, so it's clamped here rather than left to fail remotely.
     */
    private const MAX_PAGE_SIZE = 100;

    /**
     * @param  array<string, mixed>  $filters  keyWord, categoryId, startSellPrice, endSellPrice, countryCode, sort, orderBy
     */
    public function search(VendorCjCredential $credential, array $filters = [], int $page = 1, int $size = 50): CjSearchResult
    {
        $size = min($size, self::MAX_PAGE_SIZE);

        $response = $this->authenticatedRequest($credential)
            ->get('/product/listV2', [...$filters, 'page' => $page, 'size' => $size]);

        $data = $this->unwrap($response);

        // Confirmed against a real CJ response (2026-10-04): results are
        // nested as data.content[0].productList, not data.list as CJ's own
        // docs describe. The other fallbacks are kept as a safety net in
        // case this shape differs by search params or changes later.
        $items = $data['content'][0]['productList']
            ?? $data['list']
            ?? $data['content']
            ?? $data;

        $summaries = array_map(fn (array $item) => CjProductSummary::fromArray($item), $items);

        return CjSearchResult::fromArray($data, $summaries, $page);
    }

    public function productDetail(VendorCjCredential $credential, string $pid, ?string $countryCode = null): CjProductDetail
    {
        $response = $this->authenticatedRequest($credential)
            ->get('/product/query', array_filter(['pid' => $pid, 'countryCode' => $countryCode]));

        return CjProductDetail::fromArray($this->unwrap($response));
    }

    private function request(): PendingRequest
    {
        return Http::baseUrl($this->baseUrl)->acceptJson()->asJson();
    }

    private function authenticatedRequest(VendorCjCredential $credential): PendingRequest
    {
        return $this->request()->withHeaders([
            'CJ-Access-Token' => $this->accessTokenFor($credential),
        ]);
    }

    /**
     * Unwraps CJ's {code, result, message, data, success} envelope.
     * CJ can return HTTP 200 with result/success = false on a business-logic
     * failure, so that's checked explicitly rather than trusting the HTTP status alone.
     *
     * @return array<string, mixed>
     */
    private function unwrap(Response $response): array
    {
        if ($response->failed()) {
            throw new CjApiException("CJ API request failed with HTTP {$response->status()}: {$response->body()}");
        }

        $body = $response->json() ?? [];

        if (! ($body['result'] ?? $body['success'] ?? false)) {
            throw new CjApiException('CJ API returned a failure: '.($body['message'] ?? 'unknown error'));
        }

        return $body['data'] ?? [];
    }
}
