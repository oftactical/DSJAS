<?php

namespace gburtini\Hooks;

/**
 * Minimal hooks/filters system compatible with DSJAS.
 * Provides:
 *   - Hooks::bind($hook, $callback, $priority)
 *   - Hooks::run($hook, $params)
 *   - Hooks::filter($hook, $value, $params)
 *   - Hooks::setDebugLevel()
 */
class Hooks
{
    // Debug levels (bit flags)
    public const DEBUG_NONE        = 0;
    public const DEBUG_EVENTS      = 1;
    public const DEBUG_CALLS       = 2;
    public const DEBUG_BINDS       = 4;
    public const DEBUG_INTERACTION = 8;
    public const DEBUG_ALL         = 15;

    /** @var array<string, array<int, array{priority:int, callback:callable}>> */
    protected static array $bindings = [];

    /** @var int */
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

    /**
     * Bind a callback to a hook.
     */
    public static function bind(string $hook, callable $callback, int $priority = 10): void
    {
        self::debug(self::DEBUG_INTERACTION | self::DEBUG_BINDS, "Binding callback to '{$hook}' (priority {$priority})");

        self::$bindings[$hook][] = [
            'priority' => $priority,
            'callback' => $callback,
        ];

        // Sort by priority (lower number = earlier execution)
        usort(self::$bindings[$hook], static function ($a, $b): int {
            return $a['priority'] <=> $b['priority'];
        });
    }

    /**
     * Run all callbacks bound to a hook (no return value).
     *
     * @param string $hook
     * @param array<int, mixed> $params
     */
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
     * Run all callbacks bound to a hook as filters.
     * Each callback receives the current value as first argument and must return the new value.
     *
     * @param string $hook
     * @param mixed $value
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
