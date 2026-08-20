import { useEventListener } from "@vueuse/core";
import { useUserStore } from "@/stores/user";
import { AGENT_UNAVAILABLE_TIP, canUseAgent } from "@/utils/agentPermission";
import type { MentionItem } from "../at-mention-pop.vue";

export interface UseMentionOptions {
    agentList: ComputedRef<MentionItem[]>;
    inputRef: Ref<HTMLTextAreaElement | null>;
    inputContent: Ref<string>;
    emit: (event: string, ...args: any[]) => void;
    /** 是否禁用 @ 功能，默认 false */
    disableMention?: ComputedRef<boolean> | boolean;
}

export function useMention(options: UseMentionOptions) {
    const { agentList, inputRef, inputContent, emit } = options;

    const disableMention = computed(() => {
        const val = options.disableMention;
        return isRef(val) ? val.value : val ?? false;
    });

    const showMentionPop = ref(false);
    const mentionKeyword = ref("");
    const mentionStartIndex = ref(-1);
    const selectedAgent = ref<MentionItem | null>(null);
    const mentionPopRef = ref<any>(null);

    const mentionAgentList = computed(() => agentList.value);

    const openMentionPop = (atIndex: number) => {
        mentionStartIndex.value = atIndex;
        mentionKeyword.value = "";
        showMentionPop.value = true;
    };

    const closeMentionPop = () => {
        showMentionPop.value = false;
        mentionKeyword.value = "";
        mentionStartIndex.value = -1;
    };

    const clearSelectedAgent = () => {
        selectedAgent.value = null;
        emit("mention-agent", null);
    };

    const handleMentionSelect = (item: MentionItem) => {
        if (mentionStartIndex.value === -1) return;

        if (!canUseAgent(item, useUserStore().userInfo)) {
            feedback.msgWarning(AGENT_UNAVAILABLE_TIP);
            return;
        }

        const before = inputContent.value.slice(0, mentionStartIndex.value);
        const after = inputContent.value.slice(mentionStartIndex.value + 1 + mentionKeyword.value.length);

        inputContent.value = before + after;
        selectedAgent.value = item;
        emit("mention-agent", item);
        closeMentionPop();

        nextTick(() => {
            if (inputRef.value) {
                inputRef.value.focus();
                const pos = before.length;
                inputRef.value.setSelectionRange(pos, pos);
            }
        });
    };

    const handleTextareaInput = () => {
        const val = inputContent.value;
        const cursorPos = inputRef.value?.selectionStart ?? val.length;

        if (showMentionPop.value) {
            const textFromAt = val.slice(mentionStartIndex.value + 1, cursorPos);
            if (cursorPos <= mentionStartIndex.value || textFromAt.includes(" ") || textFromAt.includes("\n")) {
                closeMentionPop();
            } else {
                mentionKeyword.value = textFromAt;
            }
            return;
        }

        if (disableMention.value) return;

        const charBeforeCursor = val[cursorPos - 1];
        const charBeforeAt = val[cursorPos - 2];
        const isAtStart = cursorPos === 1;
        const isPrecededBySpace = charBeforeAt === " " || charBeforeAt === "\n" || charBeforeAt === undefined;

        if (charBeforeCursor === "@" && (isAtStart || isPrecededBySpace)) {
            if (selectedAgent.value) {
                selectedAgent.value = null;
                emit("mention-agent", null);
            }
            openMentionPop(cursorPos - 1);
        }
    };

    const handleMentionKeydown = (e: KeyboardEvent): boolean => {
        if (!showMentionPop.value) return false;

        if (e.key === "ArrowUp") {
            e.preventDefault();
            mentionPopRef.value?.moveUp();
            return true;
        }
        if (e.key === "ArrowDown") {
            e.preventDefault();
            mentionPopRef.value?.moveDown();
            return true;
        }
        if (e.key === "Enter") {
            e.preventDefault();
            mentionPopRef.value?.confirm();
            return true;
        }
        if (e.key === "Escape") {
            e.preventDefault();
            closeMentionPop();
            return true;
        }
        return true;
    };

    useEventListener(document, "click", (e: MouseEvent) => {
        if (!showMentionPop.value) return;
        const pop = mentionPopRef.value?.$el as HTMLElement;
        if (pop && !pop.contains(e.target as Node) && e.target !== inputRef.value) {
            closeMentionPop();
        }
    });

    const resetMention = () => {
        selectedAgent.value = null;
    };

    return {
        showMentionPop,
        mentionKeyword,
        selectedAgent,
        mentionAgentList,
        mentionPopRef,
        openMentionPop,
        closeMentionPop,
        clearSelectedAgent,
        handleMentionSelect,
        handleTextareaInput,
        handleMentionKeydown,
        resetMention,
    };
}
