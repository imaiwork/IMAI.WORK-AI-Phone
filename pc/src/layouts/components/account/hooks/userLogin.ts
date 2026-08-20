import { LoginPopupTypeEnum } from "@/enums/appEnums";
const loginType = ref(LoginPopupTypeEnum.MOBILE_LOGIN);
const accountLoginTypeBeforeWechat = ref(LoginPopupTypeEnum.MOBILE_LOGIN);

const isAccountLoginType = (scene: LoginPopupTypeEnum) =>
    [LoginPopupTypeEnum.LOGIN, LoginPopupTypeEnum.MOBILE_LOGIN].includes(scene);

export const useUserLogin = () => {
    const changeLoginType = (scene: LoginPopupTypeEnum) => {
        if (isAccountLoginType(scene)) {
            accountLoginTypeBeforeWechat.value = scene;
        }
        loginType.value = scene;
    };
    const toggleWechatLogin = () => {
        if (loginType.value == LoginPopupTypeEnum.WECHAT_LOGIN) {
            changeLoginType(accountLoginTypeBeforeWechat.value);
            return;
        }
        if (isAccountLoginType(loginType.value)) {
            accountLoginTypeBeforeWechat.value = loginType.value;
        }
        loginType.value = LoginPopupTypeEnum.WECHAT_LOGIN;
    };
    const closeLogin = () => {
        changeLoginType(LoginPopupTypeEnum.MOBILE_LOGIN);
    };
    return {
        loginType,
        changeLoginType,
        toggleWechatLogin,
        closeLogin,
    };
};
