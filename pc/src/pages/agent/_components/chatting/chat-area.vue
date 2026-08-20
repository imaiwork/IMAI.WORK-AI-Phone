<template>
    <div class="grow min-h-0 flex flex-col">
        <!-- 聊天窗口头部 -->
        <div class="h-[72px] flex-shrink-0 flex items-center border-b-[1px] border-[#0000000d] px-5">
            <div class="flex items-center gap-x-3 flex-1" v-if="detail">
                <div
                    class="flex-shrink-0 w-11 h-11 border p-[2px] border-[#0000000d] rounded-[10px] flex items-center justify-center">
                    <img class="w-full h-full rounded-[10px]" :src="detail.avatar || detail.image" />
                </div>
                <div>
                    <div class="font-medium line-clamp-1 break-all">{{ detail.name }}</div>
                    <div class="text-[#00000080] mt-1 line-clamp-1 break-all">
                        {{ detail.introduced || detail.intro }}
                    </div>
                </div>
            </div>
        </div>
        <!-- 仅会员可用提示 -->
        <div
            v-if="unavailable"
            class="mx-5 mt-3 flex items-center gap-2 px-4 py-2 rounded-lg border border-[#F4B400]/30 bg-[#FFF7E6] text-[#B45309] text-xs font-semibold">
            <Icon name="el-icon-WarningFilled" :size="14" />
            <span>{{ AGENT_UNAVAILABLE_TIP }}</span>
        </div>
        <!-- 聊天内容区域 -->
        <div class="grow min-h-0 pt-3" v-loading="loading">
            <slot></slot>
        </div>
    </div>
</template>

<script setup lang="ts">
import { AGENT_UNAVAILABLE_TIP } from "@/utils/agentPermission";

/**
 * @description 聊天主区域框架组件
 * @summary 包含聊天窗口的头部和用于插入具体聊天组件的插槽
 */
withDefaults(
    defineProps<{
        detail: any; // 智能体详情
        loading: boolean; // 加载状态
        unavailable?: boolean; // 是否为不可用智能体（仅会员可用且当前用户无权限）
    }>(),
    { unavailable: false },
);
</script>
