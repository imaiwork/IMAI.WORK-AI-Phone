<template>
	<scroll-view scroll-y class="h-full">
		<view class="px-4">
			<view class="bg-white rounded-[24rpx] p-4">
				<view class="flex items-center gap-2">
					<image
						src="@/ai_modules/meeting_minutes/static/images/common/loader.png"
						class="w-[48rpx] h-[48rpx]"></image>
					<view class="text-xl text-black font-bold"> 全文概要 </view>
				</view>
				<view class="mt-4">
					<template v-if="getParameters">
						<u-read-more
							toggle
							open-text="收起"
							show-height="300"
							close-text="查看全部"
							text-indent="0"
							color="#ACABB9">
							<text class="text-[#585A73] leading-6">
								{{ getParameters }}
							</text>
						</u-read-more>
					</template>
					<div class="text-[#c8cad9]" v-else>暂无全文概要</div>
				</view>
			</view>
			<view class="bg-white rounded-[24rpx] p-4 mt-4">
				<view class="flex items-center gap-2">
					<image
						src="@/ai_modules/meeting_minutes/static/images/common/loader.png"
						class="w-[48rpx] h-[48rpx]"></image>
					<view class="text-xl text-black font-bold"> 章节速览 </view>
				</view>
				<view class="mt-4">
					<template v-if="getSections">
						<u-read-more
							toggle
							show-height="800"
							open-text="收起"
							close-text="查看全部"
							color="#ACABB9"
							text-indent="0">
							<view class="flex flex-col gap-4">
								<view
									v-for="(item, index) in getSections"
									:key="index"
									class="flex gap-4">
									<view class="pt-[18rpx] relative">
										<view
											class="w-[16rpx] h-[16rpx] rounded-full bg-primary"></view>
										<view
											class="absolute h-full border border-dashed border-[#89899C] left-[6rpx] top-5"></view>
									</view>
									<view>
										<view class="flex gap-2">
											<text class="flex-shrink-0">{{
												formatAudioTime(
													item.Start / 1000
												)
											}}</text>
											<text>{{ item.Headline }} </text>
										</view>
										<view
											class="bg-[#E8F0FF] p-2 rounded mt-2">
											<text
												class="text-xs text-[#63647C]">
												{{ item.Summary }}
											</text>
										</view>
									</view>
								</view>
							</view>
						</u-read-more>
					</template>
					<view class="text-[#c8cad9]" v-else> 暂无章节速览 </view>
				</view>
			</view>
			<view class="bg-white rounded-[24rpx] p-4 mt-4">
				<view class="flex items-center gap-2">
					<image
						src="@/ai_modules/meeting_minutes/static/images/common/loader.png"
						class="w-[48rpx] h-[48rpx]"></image>
					<view class="text-xl text-black font-bold"> 问答回顾 </view>
				</view>
				<view class="mt-4">
					<template v-if="getQa">
						<u-read-more
							toggle
							show-height="800"
							open-text="收起"
							close-text="查看全部"
							color="#ACABB9"
							text-indent="0">
							<view class="flex flex-col gap-4">
								<view
									v-for="(item, index) in getQa"
									:key="index"
									class="bg-[#E8F0FF] p-3 rounded-[8rpx]">
									<view class="flex gap-4">
										<view class="flex-shrink-0 mt-1">
											<view
												class="flex items-center bg-white rounded-[24rpx]">
												<text
													class="text-primary font-bold mx-2"
													>Q:</text
												>
											</view>
										</view>
										<view class="font-bold text-[#000000]">
											{{ item.Question }}
										</view>
									</view>
									<view class="flex gap-4 mt-3">
										<view class="flex-shrink-0 mt-1">
											<view
												class="flex items-center bg-white rounded-[24rpx]">
												<text
													class="text-[#FF8D1A] font-bold mx-2"
													>A:</text
												>
											</view>
										</view>
										<view class="text-xs text-[#585A73]">
											{{ item.Answer }}
										</view>
									</view>
								</view>
							</view>
						</u-read-more>
					</template>
					<view class="text-[#c8cad9]" v-else> 暂无问答回顾 </view>
				</view>
			</view>
			<view class="bg-white rounded-[24rpx] p-4 mt-4">
				<view class="flex items-center gap-2">
					<image
						src="@/ai_modules/meeting_minutes/static/images/common/loader.png"
						class="w-[48rpx] h-[48rpx]"></image>
					<view class="text-xl text-black font-bold"> 发言总结 </view>
				</view>
				<view class="mt-4">
					<template v-if="getConversational">
						<u-read-more
							toggle
							show-height="800"
							open-text="收起"
							close-text="查看全部"
							color="#ACABB9"
							text-indent="0">
							<view class="flex flex-col gap-4">
								<view
									v-for="(item, index) in getConversational"
									:key="index"
									class="bg-[#E8F0FF] p-3 rounded-[8rpx]">
									<view class="flex items-center gap-4">
										<image
											:src="
												avatarList[item.SpeakerId - 1]
											"
											class="w-[48rpx] h-[48rpx] flex-shrink-0"></image>
										<text class="text-[#7F7F94]">{{
											item.SpeakerName
										}}</text>
									</view>
									<view class="text-xs text-[#585A73] mt-3">
										{{ item.Summary }}
									</view>
								</view>
							</view>
						</u-read-more>
					</template>
					<view class="text-[#c8cad9]" v-else> 暂无发言总结 </view>
				</view>
			</view>
			<view class="bg-white rounded-[24rpx] p-4 mt-4">
				<view class="flex items-center gap-2">
					<image
						src="@/ai_modules/meeting_minutes/static/images/common/loader.png"
						class="w-[48rpx] h-[48rpx]"></image>
					<view class="text-xl text-black font-bold"> 关键词 </view>
				</view>
				<view class="mt-4">
					<template v-if="getTags">
						<u-read-more
							toggle
							show-height="300"
							open-text="收起"
							close-text="查看全部"
							color="#ACABB9"
							text-indent="0">
							<view class="flex flex-wrap gap-2">
								<view
									v-for="(item, index) in getTags(detail)"
									:key="index"
									class="bg-[#E8F0FF] text-primary px-3 py-1 rounded-[8rpx]">
									{{ item }}
								</view>
							</view>
						</u-read-more>
					</template>
					<view class="text-[#c8cad9]" v-else> 暂无关键词 </view>
				</view>
			</view>
		</view>
	</scroll-view>
</template>

<script setup lang="ts">
import { useAppStore } from "@/stores/app";
import { formatAudioTime } from "@/utils/util";
import useHandleApi from "../../hooks/useHandleApi";

const props = defineProps<{
	detail: any;
}>();

const appStore = useAppStore();
const avatarList = computed(() => {
	return appStore.getMeetingConfig.avatars;
});

const { getTags } = useHandleApi();

// 获取全文摘要
const getParameters = computed(() => {
	const { response } = props.detail;
	if (response) {
		return response.Result?.Summarization?.Summarization?.ParagraphSummary;
	}
});

// 获取章节速览
const getSections = computed(() => {
	const { response } = props.detail;
	if (response) {
		return response.Result?.AutoChapters?.AutoChapters;
	}
});

// 获取发言总结
const getConversational = computed(() => {
	const { response } = props.detail;
	if (response) {
		return response.Result?.Summarization?.Summarization
			?.ConversationalSummary;
	}
});

// 获取问答回顾
const getQa = computed(() => {
	const { response } = props.detail;
	if (response) {
		return response.Result?.Summarization?.Summarization
			?.QuestionsAnsweringSummary;
	}
});
</script>

<style scoped></style>
