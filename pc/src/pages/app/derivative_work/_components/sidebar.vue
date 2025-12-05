<template>
	<div
		class="bg-white border-r border-r-[#e5e5e5] h-full flex flex-col min-h-0">
		<div class="flex items-center justify-between pt-[20px] px-[20px]">
			<div class="text-[14px]" v-if="false">{{ musicTypeValue }}</div>
			<div v-if="false">
				<ElDropdown>
					<div class="p-2">
						<Icon name="el-icon-MoreFilled"></Icon>
					</div>
					<template #dropdown>
						<div class="flex flex-col p-2 gap-y-2">
							<button
								class="hover:bg-token-main-surface-secondary p-2 pr-10 text-left relative"
								:class="[
									item === musicTypeValue
										? 'bg-token-main-surface-secondary'
										: '',
								]"
								v-for="(item, index) in musicTypes"
								:key="index"
								@click="chooseMusicType(item)">
								<span>
									{{ item }}
								</span>
								<span
									class="absolute right-2"
									v-if="item === musicTypeValue">
									<Icon name="el-icon-SuccessFilled"></Icon>
								</span>
							</button>
						</div>
					</template>
				</ElDropdown>
			</div>
		</div>
		<div class="mt-4 grow min-h-0 px-4">
			<ElScrollbar>
				<ElForm :model="formData" label-position="top">
					<ElFormItem label="歌曲名称" name="ask">
						<ElInput
							v-model="formData.ask"
							placeholder="请输入歌曲名称"></ElInput>
					</ElFormItem>
					<ElFormItem label="歌词风格">
						<ElInput
							v-model="formData.title"
							type="textarea"
							placeholder="请输入歌词风格"
							resize="none"
							:rows="6"></ElInput>
					</ElFormItem>
					<ElFormItem label="风格补充">
						<ElInput
							v-model="formData.tags"
							type="textarea"
							placeholder="请输入"
							resize="none"
							:rows="6"></ElInput>
					</ElFormItem>
				</ElForm>
			</ElScrollbar>
		</div>
		<div class="w-full flex justify-center py-4 px-[20px] shadow-lighter">
			<ElButton
				type="primary"
				class="!w-full !h-10"
				round
				@click="lockSubmit()"
				:loading="isLock"
				>立即生成
				<template v-if="tokensValue"
					>（消耗{{ tokensValue }}算力）</template
				>
			</ElButton>
		</div>
	</div>
</template>

<script setup lang="ts">
import { musicGenerate } from "@/api/music";
import { ToolEnum } from "@/enums/appEnums";
import { useUserStore } from "@/stores/user";
import { TokensSceneEnum } from "@/enums/appEnums";
const emit = defineEmits(["success"]);

const userStore = useUserStore();
const { userTokens } = toRefs(userStore);
const tokensValue = userStore.getTokenByScene(TokensSceneEnum.MUSIC)?.score;

enum MusicTypeEnum {
	AFFLATUS = "灵感模式",
	LYRIC = "歌词模式",
}

const musicTypeValue = ref<string>(MusicTypeEnum.AFFLATUS);
const musicTypes = ref([MusicTypeEnum.AFFLATUS, MusicTypeEnum.LYRIC]);

const formData = reactive<any>({
	ask: "",
	type: "music",
	title: "",
	tags: "",
});

const chooseMusicType = (item: string) => {
	musicTypeValue.value = item;
};

const submit = async () => {
	if (!formData.ask) {
		feedback.msgError("请输入歌曲名称");
		return;
	}
	if (userTokens.value < tokensValue) {
		feedback.msgPowerInsufficient();
		return;
	}
	await musicGenerate(formData);
	userStore.getUser();
	emit("success");
};

const { lockFn: lockSubmit, isLock } = useLockFn(submit);

const musicConfig = ref<any>({});
const getDrawConfig = async () => {};

onMounted(() => {
	getDrawConfig();
});
</script>

<style scoped></style>
