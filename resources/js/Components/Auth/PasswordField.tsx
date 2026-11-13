import { Eye, EyeOff } from "lucide-react";
import { useState } from "react";
import AuthField from "./AuthField";
import type { AuthFieldProps } from "./AuthField";

/** A password input with an eye button that reveals what was typed. */
export default function PasswordField(props: Omit<AuthFieldProps, "type" | "trailing">) {
    const [visible, setVisible] = useState(false);
    const Icon = visible ? EyeOff : Eye;

    return (
        <AuthField
            {...props}
            type={visible ? "text" : "password"}
            trailing={
                <button
                    type="button"
                    onClick={() => setVisible((current) => !current)}
                    aria-pressed={visible}
                    aria-label="إظهار كلمة المرور"
                    className="inline-flex size-11 items-center justify-center rounded-methaq-button text-methaq-muted transition-colors hover:text-methaq-primary"
                >
                    <Icon size={20} aria-hidden="true" />
                </button>
            }
        />
    );
}
