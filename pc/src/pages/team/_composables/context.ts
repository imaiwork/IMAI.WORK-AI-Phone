import type { InjectionKey } from "vue";
import type { useTeamConsole } from "./useTeamConsole";

export type TeamConsoleContext = ReturnType<typeof useTeamConsole>;

export const TEAM_CONSOLE_KEY: InjectionKey<TeamConsoleContext> = Symbol("team-console");

/** 子组件获取团队控制台上下文 */
export function useTeamContext(): TeamConsoleContext {
    const ctx = inject(TEAM_CONSOLE_KEY);
    if (!ctx) throw new Error("[team] TeamConsoleContext 未注入");
    return ctx;
}
