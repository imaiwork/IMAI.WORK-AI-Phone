<template>
    <popup
        ref="popupRef"
        width="520px"
        class="consume-detail-dialog"
        :show-close="false"
        cancel-button-text=""
        confirm-button-text=""
        footer-class="!p-0"
        header-class="!p-0"
        @close="onPopupClose">
        <div class="px-1 pb-1">
            <button class="cd-close" type="button" @click="showGenerateCard = false">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="w-4 h-4">
                    <path stroke-linecap="round" d="M6 6l12 12M18 6L6 18" />
                </svg>
            </button>

            <div class="text-center pt-3">
                <div
                    class="w-16 h-16 mx-auto rounded-2xl grid place-items-center"
                    style="
                        background: linear-gradient(135deg, #0065fb, #4f9dff);
                        box-shadow: 0 10px 24px -6px rgba(0, 101, 251, 0.5);
                    ">
                    <svg viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="1.7" class="w-8 h-8">
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.5z" />
                    </svg>
                </div>
                <h3 class="text-[19px] font-[800] text-slate-900 mt-4">生成卡密</h3>
                <p class="text-[13px] text-slate-400 mt-1.5 leading-relaxed">
                    {{
                        isMemberCard
                            ? "生成会员兑换码，用户兑换后获得对应等级与天数"
                            : "从剩余算力预扣；用户兑换后到账，删除未使用的卡密会退回"
                    }}
                </p>
            </div>

            <div class="mt-6 space-y-4">
                <div>
                    <div class="text-[13px] font-medium text-slate-500 mb-1.5">卡密类型</div>
                    <ElRadioGroup v-model="cardForm.type">
                        <ElRadio :value="5">算力卡</ElRadio>
                        <ElRadio :value="6">会员兑换码</ElRadio>
                    </ElRadioGroup>
                </div>

                <div v-if="!isMemberCard" class="grid grid-cols-2 gap-3">
                    <div>
                        <div class="text-[13px] font-medium text-slate-500 mb-1.5">每张算力</div>
                        <ElInputNumber
                            v-model="cardForm.tokens"
                            :min="1"
                            :max="999999"
                            :precision="2"
                            :step="1"
                            class="!w-full" />
                    </div>
                    <div>
                        <div class="text-[13px] font-medium text-slate-500 mb-1.5">卡密数量</div>
                        <ElInputNumber
                            v-model="cardForm.count"
                            :min="1"
                            :max="500"
                            :precision="0"
                            :step="1"
                            step-strictly
                            class="!w-full" />
                    </div>
                </div>

                <template v-else>
                    <div>
                        <div class="text-[13px] font-medium text-slate-500 mb-1.5">会员等级</div>
                        <ElSelect
                            v-model="cardForm.member_level_id"
                            placeholder="请选择目标会员等级"
                            class="!w-full"
                            :loading="memberLevelsLoading">
                            <ElOption
                                v-for="l in memberLevels"
                                :key="l.id"
                                :label="l.level_name"
                                :value="l.id" />
                        </ElSelect>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <div class="text-[13px] font-medium text-slate-500 mb-1.5">会员天数</div>
                            <ElInputNumber
                                v-model="cardForm.member_days"
                                :min="1"
                                :max="3650"
                                :precision="0"
                                :step="1"
                                step-strictly
                                class="!w-full" />
                        </div>
                        <div>
                            <div class="text-[13px] font-medium text-slate-500 mb-1.5">卡密数量</div>
                            <ElInputNumber
                                v-model="cardForm.count"
                                :min="1"
                                :max="500"
                                :precision="0"
                                :step="1"
                                step-strictly
                                class="!w-full" />
                        </div>
                    </div>
                </template>

                <div>
                    <div class="text-[13px] font-medium text-slate-500 mb-1.5">生效时间</div>
                    <ElDatePicker
                        v-model="cardForm.validRange"
                        type="datetimerange"
                        range-separator="-"
                        start-placeholder="开始"
                        end-placeholder="结束"
                        class="!w-full" />
                </div>

                <div>
                    <div class="text-[13px] font-medium text-slate-500 mb-1.5">卡号规则</div>
                    <ElRadioGroup v-model="cardForm.rule_type">
                        <ElRadio :value="1">批次号 + 随机字母</ElRadio>
                        <ElRadio :value="2">批次号 + 随机数字</ElRadio>
                    </ElRadioGroup>
                </div>

                <div>
                    <div class="text-[13px] font-medium text-slate-500 mb-1.5">备注</div>
                    <ElInput
                        v-model="cardForm.remark"
                        maxlength="200"
                        show-word-limit
                        placeholder="方便你区分批次" />
                </div>
            </div>

            <div
                v-if="!isMemberCard"
                class="flex items-center justify-between rounded-xl px-4 py-3.5 mt-5"
                style="background: linear-gradient(135deg, #eff6ff, #dbeafe)">
                <span class="text-[13px] font-medium text-blue-700">本次预计扣除</span>
                <b class="text-[22px] font-[900] text-primary leading-none">{{ cardTotalCost }}</b>
            </div>

            <div class="flex gap-3 mt-6">
                <ElButton class="!flex-1 !h-11 !rounded-xl !text-[15px]" @click="showGenerateCard = false">
                    取消
                </ElButton>
                <ElButton
                    type="primary"
                    class="!flex-1 !h-11 !rounded-xl !text-[15px] !font-semibold"
                    :loading="generatingCard"
                    @click="onGenerateCard">
                    <template v-if="isMemberCard">生成兑换码</template>
                    <template v-else>
                        生成卡密<template v-if="cardTotalCost > 0">（扣 {{ cardTotalCost }}）</template>
                    </template>
                </ElButton>
            </div>
        </div>
    </popup>
</template>

<script setup lang="ts">
import { useTeamContext } from "../_composables/context";
import { usePopupBridge } from "../_composables/usePopupBridge";

const { brand } = useTeamContext();
const {
    showGenerateCard,
    cardForm,
    cardTotalCost,
    generatingCard,
    onGenerateCard,
    memberLevels,
    memberLevelsLoading,
} = brand;
const { popupRef, onPopupClose } = usePopupBridge(showGenerateCard);

const isMemberCard = computed(() => Number(cardForm.type) === 6);
</script>
