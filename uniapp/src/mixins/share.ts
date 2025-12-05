// #ifdef H5
import wechatOa from "@/utils/wechat";
// #endif

import { useRoute } from "uniapp-router-next";
import router from "@/router";
import { useShareMessage } from "@/hooks/useShareMessage";

const onShareAppMessage = async () => {
    const { resolveOptions } = useShareMessage();
    const options = await resolveOptions();
    return options;
};
const share = {
    onShow() {
        const route = useRoute();
        const { useShare } = useShareMessage();
        const menuList = [
            "menuItem:share:appMessage",
            "menuItem:share:timeline",
            "menuItem:share:qq",
            "menuItem:share:weiboApp",
            "menuItem:share:QZone",
        ];
        if (!route.meta.share) {
            // 不允许分享的页面隐藏分享按钮
            // #ifdef MP-WEIXIN
            uni.hideShareMenu({
                hideShareItems: [],
            });
            // #endif
            // #ifdef H5
            wechatOa.hideMenuItems(menuList);
            // #endif
        } else {
            // 公众号隐藏分享按钮后切换页面需要调显示按钮api
            // #ifdef H5
            wechatOa.showMenuItems(menuList);
            useShare();
            // #endif
        }
    },
    // #ifdef MP-WEIXIN
    async onShareTimeline() {
        const { resolveOptions } = useShareMessage();
        const options = await resolveOptions();
        const route = router.currentRoute.value;
        return {
            title: options.title,
            query: route.query,
            imageUrl: options.imageUrl,
        };
    },
    async onShareAppMessage() {
        const { resolveOptions } = useShareMessage();
        const options = await resolveOptions();
        return options;
    },
    onUnload() {
        share.onShareAppMessage = onShareAppMessage;
    },
    // #endif
};
export default share;
