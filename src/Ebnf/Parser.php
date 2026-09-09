<?php

declare(strict_types=1);

namespace PhpGrammar\Ebnf;

final class Parser
{
    /** @var list<Token> */
    private array $tokens = [];
    private int $position = 0;

    public function parse(string $source): Grammar
    {
        $this->tokens = (new Lexer())->tokenize($source);
        $this->position = 0;

        $productions = [];
        while (!$this->is('eof')) {
            $productions[] = $this->parseProduction();
        }

        return new Grammar($productions);
    }

    private function parseProduction(): Production
    {
        $name = $this->expect('identifier');
        $this->expect('=');
        $expression = $this->parseExpression([';']);
        $this->expect(';');

        return new Production($name->value, $expression, $name->line);
    }

    /**
     * @param non-empty-list<string> $terminators
     */
    private function parseExpression(array $terminators): Node
    {
        $alternatives = [$this->parseSequence(array_merge($terminators, ['|']))];

        while ($this->match('|')) {
            if ($this->isAny($terminators) || $this->is('|')) {
                throw $this->error('Expected expression after alternative marker.');
            }

            $alternatives[] = $this->parseSequence(array_merge($terminators, ['|']));
        }

        return count($alternatives) === 1 ? $alternatives[0] : new AlternativeNode($alternatives);
    }

    /**
     * @param non-empty-list<string> $terminators
     */
    private function parseSequence(array $terminators): Node
    {
        if ($this->isAny($terminators) || $this->is('eof')) {
            throw $this->error('Expected expression.');
        }

        $elements = [$this->parseTerm()];
        while ($this->match(',')) {
            if ($this->isAny($terminators) || $this->is(',')) {
                throw $this->error('Expected expression after sequence marker.');
            }

            $elements[] = $this->parseTerm();
        }

        return count($elements) === 1 ? $elements[0] : new SequenceNode($elements);
    }

    private function parseTerm(): Node
    {
        if ($this->match('identifier', $token)) {
            return new ReferenceNode($token->value);
        }

        if ($this->match('literal', $token)) {
            return new LiteralNode($token->value);
        }

        if ($this->match('(')) {
            $expression = $this->parseExpression([')']);
            $this->expect(')');
            return new GroupNode($expression);
        }

        if ($this->match('[')) {
            $expression = $this->parseExpression([']']);
            $this->expect(']');
            return new OptionalNode($expression);
        }

        if ($this->match('{')) {
            $expression = $this->parseExpression(['}']);
            $this->expect('}');
            return new RepetitionNode($expression);
        }

        throw $this->error('Expected identifier, literal, group, optional, or repetition.');
    }

    private function expect(string $type): Token
    {
        if ($this->match($type, $token)) {
            return $token;
        }

        throw $this->error(sprintf('Expected %s.', $type));
    }

    private function match(string $type, ?Token &$token = null): bool
    {
        if (!$this->is($type)) {
            return false;
        }

        $token = $this->tokens[$this->position];
        $this->position++;
        return true;
    }

    private function is(string $type): bool
    {
        return $this->tokens[$this->position]->type === $type;
    }

    /**
     * @param list<string> $types
     */
    private function isAny(array $types): bool
    {
        return in_array($this->tokens[$this->position]->type, $types, true);
    }

    private function error(string $message): ParserException
    {
        $token = $this->tokens[$this->position];

        return new ParserException(sprintf(
            '%s Found %s at line %d, column %d.',
            $message,
            $token->type === 'eof' ? 'end of file' : var_export($token->value, true),
            $token->line,
            $token->column,
        ));
    }
}
