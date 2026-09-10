<?php

declare(strict_types=1);

namespace PhpGrammar\Php\Lexing;

enum TokenType: string
{
    case InlineHtml = 'inline-html';
    case OpenTag = 'open-tag';
    case EchoOpenTag = 'echo-open-tag';
    case CloseTag = 'close-tag';
    case Whitespace = 'whitespace';
    case Comment = 'comment';
    case DocComment = 'doc-comment';
    case Identifier = 'identifier';
    case QualifiedName = 'qualified-name';
    case FullyQualifiedName = 'fully-qualified-name';
    case RelativeName = 'relative-name';
    case Keyword = 'keyword';
    case Variable = 'variable';
    case IntegerLiteral = 'integer-literal';
    case FloatingLiteral = 'floating-literal';
    case StringLiteral = 'string-literal';
    case BacktickString = 'backtick-string';
    case HaltCompilerData = 'halt-compiler-data';
    case HeredocString = 'heredoc-string';
    case NowdocString = 'nowdoc-string';
    case Operator = 'operator';
    case Punctuation = 'punctuation';
}
