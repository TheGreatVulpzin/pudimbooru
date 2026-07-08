# User Moderation TODO

- [x] Link account bans to IP Ban without overwriting manual IP bans.
- [x] Store IP evidence for account bans in `user_moderation_ip_links`.
- [x] Detect ban evasion from stored ban evidence, not loose log history.
- [x] Add configuration for automatic evasion bans and IP ban behavior.
- [x] Add detect-only mode for ban evasion so production can log matches without auto-banning.
- [x] Show IP evidence to staff on moderation history or user moderation panels.
- [x] Remove IP bans created by an account ban when that moderation action ends.
- [ ] Add full database-backed tests once local test DB driver is available.
