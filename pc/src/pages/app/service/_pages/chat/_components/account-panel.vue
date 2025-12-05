<template>
    <div class="h-full">
        <ElScrollbar>
            <div class="py-4 px-2">
                <div
                    v-for="(item, index) in accountLists"
                    :key="index"
                    class="w-full h-[66px] mx-auto flex items-center justify-center cursor-pointer rounded hover:bg-primary-light-9"
                    :class="{
                        'bg-primary-light-9': currentAccount === item.account,
                    }"
                    @click="handleSelectAccount(item)">
                    <ElTooltip :content="item.nickname" placement="right">
                        <div class="relative w-[48px] h-[48px] rounded">
                            <div class="w-full h-full rounded">
                                <img :src="item.avatar" alt="" class="w-full h-full rounded" />
                            </div>
                            <div class="absolute -right-1 -top-1 z-50" v-if="item.msg_count > 0 && item.status == 1">
                                <ElBadge
                                    :value="item.msg_count"
                                    color="#EA3323"
                                    :badge-style="{
                                        border: 'none',
                                    }">
                                </ElBadge>
                            </div>

                            <div
                                class="absolute top-0 left-0 w-full h-full bg-black/5 z-[888] rounded"
                                v-if="item.loading">
                                <div class="w-full h-full rounded bg-gray-200 animate-pulse"></div>
                            </div>
                            <template v-else>
                                <div
                                    class="absolute w-full h-full top-0 left-0 flex items-center justify-center bg-[#BABABAAF] z-40 rounded"
                                    v-if="item.status != 1">
                                    <Icon name="local-icon-offline" :size="24"></Icon>
                                </div>
                            </template>
                        </div>
                    </ElTooltip>
                </div>
            </div>
        </ElScrollbar>
    </div>
</template>

<script setup lang="ts">
import useAccount from "../../../_hooks/useAccount";

const { accountLists, currentAccount, handleSelectAccount } = useAccount();
</script>

<style scoped></style>
