<?php

namespace Thrust\S3;

use Phalcon\Mvc\User\Component;

/**
 * Thrust\S3\S3
 * Interacts with AWS S3
 */
class S3 extends Component
{
    protected $amazonS3;

    /**
     * Delete all objects tied to a parent in S3
	 * @param string $bucket
	 * @param string $parent
     * @return bool
     */
    public function matchDelete($bucket, $prefix)
    {
        $logger = \Phalcon\Di::getDefault()->getShared('logger');
        try {
            if ($this->amazonS3 == null) {
                $this->amazonS3 = new  \Aws\S3\S3Client([
                    'version' => 'latest',
                    'region' => $this->config->awsS3->region,
                    'credentials'   => [
                        'key'    => $this->config->awsS3->key,
                        'secret' => $this->config->awsS3->secret,
                    ],
                ]);
            }
            $this->amazonS3->deleteMatchingObjects($bucket, $prefix); 
        } catch (\Exception $e) {
            $logger->error('AWS S3 Error: ' . $e->getMessage());
        }
    }

    /**
     * Upload a file to a bucket
     * @param string $bucket
     * @param string $parent
     * @return bool
     */
    public function upload($bucket, $key, $file)
    {
        $logger = \Phalcon\Di::getDefault()->getShared('logger');
        try {
            if ($this->amazonS3 == null) {
                $this->amazonS3 = new  \Aws\S3\S3Client([
                    'version' => 'latest',
                    'region' => $this->config->awsS3->region,
                    'credentials'   => [
                        'key'    => $this->config->awsS3->key,
                        'secret' => $this->config->awsS3->secret,
                    ],
                ]);
            }
            $this->amazonS3->putObject(array(
                'Bucket'     => $bucket,
                'Key'        => $key,
                'SourceFile' => $file,
            )); 
        } catch (\Exception $e) {
            $logger->error('AWS S3 Error: ' . $e->getMessage());
        }
    }
}
