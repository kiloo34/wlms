import { Form, Head } from '@inertiajs/react';
import InputError from '@/components/input-error';
import PasswordInput from '@/components/password-input';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { store } from '@/routes/password/confirm';
import {
    index as confirmOptions,
    store as confirmStore,
} from '@/actions/Laravel/Passkeys/Http/Controllers/PasskeyConfirmationController';
import PasskeyVerify from '@/components/passkey-verify';
import { Card, CardContent, CardDescription, CardHeader, CardTitle, CardFooter } from '@/components/ui/card';

export default function ConfirmPassword() {
    return (
        <>
            <Head title="Security Verification" />

            <div className="space-y-8">
                <Card>
                    <CardHeader>
                        <CardTitle>Security Verification</CardTitle>
                        <CardDescription>
                            You are trying to access a sensitive area or perform a highly secure action. Please confirm your password to verify your identity.
                        </CardDescription>
                    </CardHeader>

                    <div className="pt-6 px-6 max-w-2xl space-y-8">
                        <PasskeyVerify
                            routes={{
                                options: confirmOptions(),
                                submit: confirmStore(),
                            }}
                            label="Confirm with passkey"
                            loadingLabel="Confirming..."
                            separator="Or confirm with password"
                        />
                    </div>

                    <Form {...store.form()} resetOnSuccess={['password']}>
                        {({ processing, errors }) => (
                            <>
                                <CardContent >
                                    <div className="max-w-xl space-y-2">
                                        <Label htmlFor="password">Password</Label>
                                        <PasswordInput
                                            id="password"
                                            name="password"
                                            placeholder="Enter your current password"
                                            autoComplete="current-password"
                                            autoFocus
                                            className="w-full max-w-md"
                                        />
                                        <InputError message={errors.password} />
                                    </div>
                                </CardContent>
                                <CardFooter className="justify-end">
                                    <Button
                                        
                                        disabled={processing}
                                        data-test="confirm-password-button"
                                    >
                                        {processing && <Spinner className="mr-2" />}
                                        Confirm Password
                                    </Button>
                                </CardFooter>
                            </>
                        )}
                    </Form>
                </Card>
            </div>
        </>
    );
}

ConfirmPassword.layout = {
    breadcrumbs: [
        {
            title: 'Security Verification',
            href: '/user/confirm-password',
        },
    ],
};
