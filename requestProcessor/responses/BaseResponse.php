<?php

namespace treatstock\api\v2\requestProcessor\responses;

use treatstock\api\v2\exceptions\InvalidAnswerException;
use treatstock\api\v2\exceptions\InvalidAnswerModelException;
use treatstock\api\v2\exceptions\UnSuccessException;
use treatstock\api\v2\models\ModelReflectionService;

abstract class BaseResponse
{
    /**
     */
    public $model;

    /**
     * @var ModelReflectionService
     */
    public $modelReflectionService;

    /**
     * BaseResponse constructor.
     *
     * @param $httpData
     * @throws InvalidAnswerException
     * @throws InvalidAnswerModelException
     * @throws UnSuccessException
     */
    public function __construct($httpData)
    {
        $modelClass = $this->getModelClass();
        $this->model = new $modelClass();
        $this->checkForSuccess($httpData);
        $this->loadModel($httpData);
        if ($errors = $this->model->validateAndGetErrors()) {
            throw new InvalidAnswerModelException($this->model, $errors);
        }
    }

    abstract public function getModelClass();

    abstract public function loadModel($data);


    /**
     * @param $httpData
     * @throws InvalidAnswerException
     * @throws UnSuccessException
     */
    protected function checkForSuccess($httpData)
    {
        if (!is_array($httpData)) {
            throw new InvalidAnswerException('Invalid format, not array.');
        }
        if (array_key_exists('success', $httpData) && (!$httpData['success'])) {
            $message = 'Unsuccessful operation';
            $errors = [];
            if (array_key_exists('errors', $httpData) && is_array($httpData['errors']) && $httpData['errors']) {
                $errors = $httpData['errors'];
            }
            if (array_key_exists('message', $httpData) && $httpData['message']) {
                $message .= ': ' . $httpData['message'];
            }
            if (array_key_exists('reason', $httpData) && $httpData['reason']) {
                $message .= ': ' . $httpData['reason'];
            }
            throw new UnSuccessException($message, $errors);
        }
        if (array_key_exists('code', $httpData) && array_key_exists('name', $httpData) && array_key_exists('message', $httpData)) {
            throw new InvalidAnswerException($httpData['name'] . ' ' . $httpData['message'].".\n");
        }
    }

    /**
     * @return ModelReflectionService
     */
    public function getModelReflectionService()
    {
        if (!$this->modelReflectionService) {
            $this->modelReflectionService = ModelReflectionService::getInstance();
        }
        return $this->modelReflectionService;
    }

    /**
     * @param array $attributes
     * @param $model
     * @param array $data
     * @throws \ReflectionException
     */
    protected function loadAttributes($attributes, $model, $data)
    {
        if (!$attributes) {
            $attributes = $this->getModelReflectionService()->getSimpleAttributesList($model);
        }
        foreach ($attributes as $attributeDataName => $attributeName) {
            if (((int)$attributeDataName) === $attributeDataName) {
                // is int index
                $attributeDataName = $attributeName;
            }
            if (array_key_exists($attributeName, $data)) {
                $value = $data[$attributeDataName];
                $valueFormatted = $this->getModelReflectionService()->formatAttributeValue($model, $attributeName, $value);
                $model->$attributeName = $valueFormatted;
            }
        }
    }

    protected function initClassAttributes($class, &$model, $data)
    {
        if ($data === null) {
            return;
        }
        $model = new $class();
        $attributes = $this->getModelReflectionService()->getSimpleAttributesList($model);
        foreach ($attributes as $attributeName) {
            if (array_key_exists($attributeName, $data)) {
                $model->$attributeName = $data[$attributeName];
            }
        }
    }
}