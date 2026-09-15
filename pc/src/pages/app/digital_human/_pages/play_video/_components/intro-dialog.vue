<template>
    <ElDialog
        :model-value="modelValue"
        width="520px"
        align-center
        :title="`${playName} · 玩法原理`"
        @update:model-value="emit('update:modelValue', $event)"
    >
        <div class="text-[12px] text-[#9CA3AF] mb-4 -mt-2">
            {{ intro.note }}
        </div>
        <div class="flex items-start gap-1">
            <template v-for="(st, i) in intro.steps" :key="i">
                <span v-if="i" class="text-[#B9C6DB] mt-5">›</span>
                <div class="flex-1 text-center">
                    <div
                        class="w-10 h-10 rounded-xl mx-auto mb-1.5 flex items-center justify-center"
                        :style="{ background: st.bg }"
                    >
                        <Icon
                            :name="`local-icon-${st.icon}`"
                            :size="20"
                            :color="st.fg"
                        />
                    </div>
                    <div class="text-[12px] font-bold text-[#1F2937]">
                        {{ st.t }}
                    </div>
                    <div class="text-[11px] text-[#9CA3AF] mt-0.5 leading-snug">
                        {{ st.d }}
                    </div>
                </div>
            </template>
        </div>
        <div class="rounded-xl p-4 mt-5 bg-[#F5F8FF]">
            <div class="text-[13px] font-bold text-[#1F2937] mb-2">怎么做</div>
            <div
                v-for="(h, i) in intro.how"
                :key="i"
                class="text-[12px] text-[#4B5563] leading-relaxed"
            >
                {{ i + 1 }}. {{ h }}
            </div>
        </div>
        <div class="text-[11px] text-[#9CA3AF] leading-relaxed mt-4">
            {{ intro.tip }}
        </div>
    </ElDialog>
</template>

<script setup lang="ts">
import { PLAYS, PLAY_INTRO } from '../_enums'
import type { PlayKey } from '../_typings'

const props = defineProps<{ modelValue: boolean; play: PlayKey }>()
const emit = defineEmits<{ (e: 'update:modelValue', value: boolean): void }>()

const intro = computed(() => PLAY_INTRO[props.play])
const playName = computed(
    () => PLAYS.find((p) => p.key === props.play)?.name || ''
)
</script>
