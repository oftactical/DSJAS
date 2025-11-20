<?php

namespace gburtini\Hooks;

/**
 * Minimal hooks/filters system for DSJAS.
 */
class Hooks
{
    public const DEBUG_NONE        = 0;
    public const DEBUG_EVENTS      = 1;
    public const DEBUG_CALLS       = 2;
    public const DEBUG_BINDS       = 4;
    public const DEBUG_INTERACTION = 8;
    public const DEBUG_ALL         = 15;

    /** @var array<string, array<int, array{priority:int, callback:callable}>> */
    protected static array $bindings = [];

    protected static int $debugLevel = self::DEBUG_NONE;

    public static function setDebugLevel(int $level): void
    {
        self::$debugLevel = $level;
    }

    protected static function debug(int $level, string $message): void
    {
        if ((self::$debugLevel & $level) !== 0) {
            error_log('[Hooks] ' . $message);
        }
    }

    public static function bind(string $hook, callable $callback, int $priority = 10): void
    {
        self::debug(self::DEBUG_INTERACTION | self::DEBUG_BINDS, "Binding callback to '{$hook}' (priority {$priority})");

        self::$bindings[$hook][] = [
            'priority' => $priority,
            'callback' => $callback,
        ];

        usort(self::$bindings[$hook], static function ($a, $b): int {
            return $a['priority'] <=> $b['priority'];
        });
    }

    public static function run(string $hook, array $params = []): void
    {
        self::debug(self::DEBUG_INTERACTION | self::DEBUG_EVENTS, "Running hook '{$hook}'");

        if (empty(self::$bindings[$hook])) {
            return;
        }

        foreach (self::$bindings[$hook] as $binding) {
            self::debug(self::DEBUG_CALLS, "Calling callback for '{$hook}'");
            \call_user_func_array($binding['callback'], $params);
        }
    }

    /**
     * Filter-style hook: each callback gets the current $value and returns a new one.
     *
     * @param string $hook
     * @param mixed  $value
     * @param array<int, mixed> $params
     * @return mixed
     */
    public static function filter(string $hook, $value, array $params = [])
    {
        self::debug(self::DEBUG_INTERACTION | self::DEBUG_EVENTS, "Filtering via hook '{$hook}'");

        if (empty(self::$bindings[$hook])) {
            return $value;
        }

        foreach (self::$bindings[$hook] as $binding) {
            self::debug(self::DEBUG_CALLS, "Calling filter callback for '{$hook}'");
            $value = \call_user_func_array(
                $binding['callback'],
                array_merge([$value], $params)
            );
        }

        return $value;
    }
}