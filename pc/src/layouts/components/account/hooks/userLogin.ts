import { LoginPopupTypeEnum } from "@/enums/appEnums";
const loginType = ref(LoginPopupTypeEnum.MOBILE_LOGIN);
export const useUserLogin = () => {
    const changeLoginType = (scene: LoginPopupTypeEnum) => {
        loginType.value = scene;
    };
    const closeLogin = () => {
        loginType.value = LoginPopupTypeEnum.MOBILE_LOGIN;
    };
    return {
        loginType,
        changeLoginType,
        closeLogin,
    };
};
