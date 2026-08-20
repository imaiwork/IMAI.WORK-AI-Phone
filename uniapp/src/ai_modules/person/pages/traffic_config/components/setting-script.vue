<template>
    <view>
        <config-card
            title="评论区引流话术"
            desc="评论区回复，引导用户看私信或主页"
            icon-name="chat"
            icon-color="#00C08E"
            icon-bg="#E6F8F3">
            <script-list
                :items="configData.commentScripts"
                add-text="添加话术"
                @add="emit('add', 'commentScripts')"
                @edit="emit('edit', 'commentScripts', $event)"
                @remove="emit('remove', 'commentScripts', $event)" />
        </config-card>

        <config-card
            title="私信转化话术"
            desc="自动发送私信，直接留资或成交"
            icon-name="email"
            icon-color="#0065FB"
            icon-bg="#E6F0FF">
            <script-list
                :items="configData.dmScripts"
                type="textarea"
                add-text="添加话术"
                @add="emit('add', 'dmScripts')"
                @edit="emit('edit', 'dmScripts', $event)"
                @remove="emit('remove', 'dmScripts', $event)" />
        </config-card>
    </view>
</template>

<script setup lang="ts">
const props = defineProps<{
    configData: any;
}>();

const emit = defineEmits<{
    (e: "add", key: string): void;
    (e: "edit", key: string, value: any): void;
    (e: "remove", key: string, value: any): void;
}>();
</script>

<style scoped></style>
