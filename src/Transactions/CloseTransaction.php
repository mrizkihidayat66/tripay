<?php

namespace Nekoding\Tripay\Transactions;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;
use Nekoding\Tripay\Exceptions\InvalidSignatureHashException;
use Nekoding\Tripay\Networks\HttpClient;
use Nekoding\Tripay\Signature;
use Nekoding\Tripay\Validator\CreateCloseTransactionFormValidation;
use Psr\Http\Message\ResponseInterface;

class CloseTransaction implements Transaction
{

    /**
     * @var HttpClient
     */
    protected $httpClient;

    /**
     * @var string
     */
    protected string $response;

    /**
     * @param HttpClient $httpClient
     */
    public function __construct(HttpClient $httpClient)
    {
        $this->httpClient = $httpClient;
    }

    /**
     * @param array $data
     * @return Transaction
     * @throws InvalidSignatureHashException
     * @throws \Illuminate\Validation\ValidationException
     */
    public function createTransaction(array $data): Transaction
    {
        $validated = CreateCloseTransactionFormValidation::validate($data);

        if (!Signature::validate(
            $this->setSignatureHash($validated['merchant_ref'] . $validated['amount']), 
            $validated['signature'])
            ) {
            throw new InvalidSignatureHashException("signature hash tidak valid.");
        }

        $this->response = $this->httpClient->sendRequest('POST', 'transaction/create', $validated);

        return $this;
    }

    /**
     * @param string $refNumber
     * @return Transaction
     * @throws \Illuminate\Validation\ValidationException
     */
    public function getDetailTransaction(string $refNumber): Transaction
    {
        $validated = Validator::make([
            'reference' => $refNumber
        ], [
            'reference' => 'required|string'
        ])->validate();

        $this->response = $this->httpClient->sendRequest('GET', 'transaction/detail', $validated);

        return $this;
    }

    /**
     * @return Collection
     */
    public function getResponse(): Collection
    {
        return collect(json_decode($this->response, true));
    }

    /**
     * @param string $data
     * @return string
     */
    public function setSignatureHash(string $data): string
    {
        return config('tripay.tripay_merchant_code') . $data;
    }
}