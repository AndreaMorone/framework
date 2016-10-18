<?php


namespace GigaAI\Core\Rule;


use SuperClosure\Serializer;

/**
 * Class RuleManager
 *
 * @package GigaAI\Core
 */
class RuleManager
{
    /**
     * @var Rule[]
     */
    private $rules = [];

    /**
     * @var Rule
     */
    private $currentRule;

    /**
     * @var RuleRepositoryInterface
     */
    private $ruleRepository;

    /**
     * @var Serializer
     */
    private $serializer;

    /**
     * RuleManager constructor.
     *
     * @param RuleRepositoryInterface $ruleRepository
     * @param Serializer $serializer
     */
    public function __construct(RuleRepositoryInterface $ruleRepository, Serializer $serializer)
    {
        $this->ruleRepository = $ruleRepository;
        $this->serializer = $serializer;
    }

    /**
     * Load all rules & check if there is any existing rule
     *
     * @return bool
     */
    public function initialized()
    {
        $rules = $this->ruleRepository->getAll();

        if ($rules) {
            foreach ($rules as &$rule) {
                /** @var Rule $rule */
                $rule->response = json_decode($rule->response, true);

                $this->rules[] = $rule;
            }
        }

        return !empty($this->rules);
    }

    /**
     * Add a rule
     *
     * @param $request
     * @param $response
     */
    public function addRule($request, $response)
    {
        $rule = new Rule([
            'request' => $request,
            'response' => json_encode($response)
        ]);

        $this->currentRule = $rule;

        $this->ruleRepository->save($rule);

        $this->rules[] = $rule;
    }

    /**
     * Add handler for a rule.
     * Handler will be serialize before saving
     *
     * @param callable $callback
     */
    public function addThenHandler(callable $callback)
    {
        if (!$this->currentRule) {
            return;
        }

        $serializedCallback = $this->serializer->serialize($callback);
        $this->currentRule->thenHandler = $serializedCallback;

        $this->ruleRepository->save($this->currentRule);
    }

    /**
     * Return a rule by its id
     * If rule has thenHandler, then it should be unserialized
     *
     * @param $ruleId
     * @return Rule|null
     */
    public function getById($ruleId)
    {
        $foundRule = null;
        foreach ($this->rules as $rule) {
            if ((int)$rule->id === (int)$ruleId) {
                $foundRule = $rule;
                break;
            }
        }

        if ($foundRule && $foundRule->thenHandler) {
            $foundRule->thenHandler = $this->serializer->unserialize($foundRule->thenHandler);
        }

        return $foundRule;
    }

    /**
     * Return all rules
     *
     * @return Rule[]|mixed
     */
    public function getAll()
    {
        return empty($this->rules) ? $this->getAll() : $this->rules;
    }
}