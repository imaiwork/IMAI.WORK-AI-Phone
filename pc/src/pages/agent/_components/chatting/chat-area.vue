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
                    <div class="font-bold line-clamp-1 break-all">{{ detail.name }}</div>
                    <div class="text-[#00000080] mt-1 line-clamp-1 break-all">
                        {{ detail.introduced || detail.intro }}
                    </div>
                </div>
            </div>
        </div>
        <!-- 聊天内容区域 -->
        <div class="grow min-h-0" v-loading="loading">
            <slot></slot>
        </div>
    </div>
</template>

<script setup lang="ts">
/**
 * @description 聊天主区域框架组件
 * @summary 包含聊天窗口的头部和用于插入具体聊天组件的插槽
 */
defineProps<{
    detail: any; // 智能体详情
    loading: boolean; // 加载状态
}>();
</script>
