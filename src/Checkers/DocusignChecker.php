<?php

namespace PragmaRX\Health\Checkers;

use DocuSign\eSign\ApiClient;
use DocuSign\eSign\Configuration;
use DocuSign\eSign\Api\AuthenticationApi;
use DocuSign\eSign\Api\AuthenticationApi\LoginOptions;

class DocusignChecker extends BaseChecker
{
    /**
     * @return bool
     */
    public function check()
    {
        if (! $this->login()) {
            throw new Exception('Unable to authenticate to the DocuSign Api');
        }

        return $this->makeHealthyResult();
    }

    private function getAccountIdFromLogin($login)
    {
        return $login->getLoginAccounts()[0]->getAccountId();
    }

    /**
     * @param $config
     * @return ApiClient
     */
    protected function getApiClient($config)
    {
        return new ApiClient($config);
    }

    /**
     * @param $config
     * @return AuthenticationApi
     */
    protected function getAuthApi($config)
    {
        return new AuthenticationApi($this->getApiClient($config));
    }

    /**
     * @return ApiClient
     */
    protected function getConfig()
    {
        return (new Configuration)
            ->setDebug($this->resource['debug'])
            ->setDebugFile($this->resource['debug_file'])
            ->setHost($this->resource['api_host'])
            ->addDefaultHeader(
                'X-DocuSign-Authentication',
                json_encode([
                                'Username'      => $this->resource['email'],
                                'Password'      => $this->resource['password'],
                                'IntegratorKey' => $this->resource['integrator_key'],
                            ])
            );
    }

    /**
     * @param $config
     * @return \DocuSign\eSign\Model\LoginInformation
     */
    protected function getLoginInformation($config)
    {
        return $this->getAuthApi($config)->login($this->getLoginOptions());
    }

    /**
     * @return LoginOptions
     */
    protected function getLoginOptions()
    {
        return new LoginOptions();
    }

    /**
     * @return mixed
     */
    protected function login()
    {
        return $this->getAccountIdFromLogin(
            $this->getLoginInformation(
                $this->getConfig()
            )
        );
    }
}
