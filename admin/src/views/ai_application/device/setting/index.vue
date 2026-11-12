<template>
    <div>
        <el-card class="!border-none" shadow="never">
            <div class="text-xl font-medium mb-[20px]">基本配置</div>
            <el-form :model="formData" label-width="140px">
                <el-form-item label="手机演示功能">
                    <div>
                        <el-switch
                            v-model="formData.demo_switch"
                            :active-value="DEMO_SWITCH_ON"
                            :inactive-value="DEMO_SWITCH_OFF"
                        />
                        <div class="form-tips">开启后，前台将展示手机演示相关功能</div>
                    </div>
                </el-form-item>
            </el-form>
        </el-card>

        <el-card class="!border-none mt-4" shadow="never">
            <div class="text-xl font-medium mb-[20px]">手动任务开关</div>
            <el-form :model="formData" label-width="140px">
                <el-form-item label="微信任务">
                    <div class="flex flex-col gap-3">
                        <div
                            v-for="item in wechatTaskList"
                            :key="item.key"
                            class="flex items-center gap-3"
                        >
                            <el-switch
                                v-model="formData.manual_task_switch[item.key]"
                                :active-value="1"
                                :inactive-value="0"
                            />
                            <span class="text-sm text-tx-secondary">{{ item.label }}</span>
                        </div>
                    </div>
                </el-form-item>
                <el-form-item label="视频号任务">
                    <div class="flex flex-col gap-3">
                        <div
                            v-for="item in sphTaskList"
                            :key="item.key"
                            class="flex items-center gap-3"
                        >
                            <el-switch
                                v-model="formData.manual_task_switch[item.key]"
                                :active-value="1"
                                :inactive-value="0"
                            />
                            <span class="text-sm text-tx-secondary">{{ item.label }}</span>
                        </div>
                    </div>
                </el-form-item>
                <el-form-item label="抖音任务">
                    <div class="flex flex-col gap-3">
                        <div
                            v-for="item in douyinTaskList"
                            :key="item.key"
                            class="flex items-center gap-3"
                        >
                            <el-switch
                                v-model="formData.manual_task_switch[item.key]"
                                :active-value="1"
                                :inactive-value="0"
                            />
                            <span class="text-sm text-tx-secondary">{{ item.label }}</span>
                        </div>
                        <div class="form-tips">
                            关闭后前台隐藏「自动添加好友」设置，且不会生成加好友任务
                        </div>
                    </div>
                </el-form-item>
                <el-form-item label="">
                    <div class="form-tips">
                        关闭后前台将隐藏对应任务入口，同时后端拒绝创建该任务
                    </div>
                </el-form-item>
            </el-form>
        </el-card>
    </div>
    <footer-btns>
        <el-button
            v-perms="['ai_application.device/setConfig']"
            type="primary"
            :loading="isLock"
            @click="lockSubmit"
        >
            保存
        </el-button>
    </footer-btns>
</template>

<script setup lang="ts">
import { saveConfig } from '@/api/app'
import { useLockFn } from '@/hooks/useLockFn'
import useAppStore from '@/stores/modules/app'

const DEMO_SWITCH_OFF = 0
const DEMO_SWITCH_ON = 1

const wechatTaskList = [
    { key: 'wechat_friend_add', label: '自动加好友' },
    { key: 'wechat_private_take', label: '个微接管' },
    { key: 'wechat_circle_publish', label: '发布朋友圈' },
    { key: 'wechat_circle_interact', label: '朋友圈互动' }
]

const sphTaskList = [
    { key: 'sph_clue', label: '自动获线索' },
    { key: 'sph_private_take', label: '评论接管（视频号）' }
]

const DY_SEARCH_FRIEND_ADD_KEY = 'dy_search_friend_add'

const douyinTaskList = [{ key: DY_SEARCH_FRIEND_ADD_KEY, label: '自动加好友（抖音账号搜寻获客）' }]

const manualTaskKeys = [...wechatTaskList, ...sphTaskList, ...douyinTaskList].map(
    (item) => item.key
)

// 未配置的开关默认关闭（与后端 ManualTaskSwitchService 口径一致）
const buildManualTaskSwitch = (config: Record<string, any> = {}) => {
    const result: Record<string, number> = {}
    manualTaskKeys.forEach((key) => {
        result[key] = Number(config?.[key] ?? 0) === 1 ? 1 : 0
    })
    return result
}

const appStore = useAppStore()

const formData = reactive({
    demo_switch: Number(appStore.config?.demo_switch ?? DEMO_SWITCH_OFF)
        ? DEMO_SWITCH_ON
        : DEMO_SWITCH_OFF,
    manual_task_switch: buildManualTaskSwitch(appStore.config?.manual_task_switch)
})

const handleSubmit = async () => {
    const demoSwitch = Number(formData.demo_switch) ? DEMO_SWITCH_ON : DEMO_SWITCH_OFF
    await saveConfig({
        type: 'rpa',
        name: 'demo_switch',
        data: demoSwitch
    })
    appStore.config = {
        ...appStore.config,
        demo_switch: demoSwitch
    }
    formData.demo_switch = demoSwitch

    const manualTaskSwitch = buildManualTaskSwitch(formData.manual_task_switch)
    await saveConfig({
        type: 'rpa',
        name: 'manual_task_switch',
        data: manualTaskSwitch
    })
    appStore.config = {
        ...appStore.config,
        manual_task_switch: manualTaskSwitch
    }
    formData.manual_task_switch = manualTaskSwitch
}

const { lockFn: lockSubmit, isLock } = useLockFn(handleSubmit)
</script>

<style lang="scss" scoped></style>
