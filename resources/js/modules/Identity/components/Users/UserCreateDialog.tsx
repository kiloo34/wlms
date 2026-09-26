import { useState } from 'react';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Plus } from 'lucide-react';
import { useCreateUser } from '../../hooks/use-users';
import { toast } from 'sonner';

export function UserCreateDialog() {
    const [isOpen, setIsOpen] = useState(false);
    const [name, setName] = useState('');
    const [email, setEmail] = useState('');
    const [password, setPassword] = useState('');
    
    const createUser = useCreateUser();

    const handleSubmit = async (e: React.FormEvent) => {
        e.preventDefault();
        try {
            await createUser.mutateAsync({ name, email, password: password || undefined });
            toast.success('User created successfully');
            setIsOpen(false);
            setName('');
            setEmail('');
            setPassword('');
        } catch (error) {
            toast.error('Failed to create user');
        }
    };

    return (
        <Dialog open={isOpen} onOpenChange={setIsOpen}>
            <DialogTrigger asChild>
                <Button className="bg-blue-600 hover:bg-blue-700 text-white">
                    <Plus className="w-4 h-4 mr-2" /> Create User
                </Button>
            </DialogTrigger>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Create New User</DialogTitle>
                </DialogHeader>
                <form onSubmit={handleSubmit} className="space-y-4">
                    <div className="space-y-2">
                        <Label htmlFor="name">Name</Label>
                        <Input 
                            id="name" 
                            value={name} 
                            onChange={(e) => setName(e.target.value)} 
                            placeholder="e.g. John Doe"
                            required 
                        />
                    </div>
                    <div className="space-y-2">
                        <Label htmlFor="email">Email</Label>
                        <Input 
                            id="email" 
                            type="email"
                            value={email} 
                            onChange={(e) => setEmail(e.target.value)} 
                            placeholder="e.g. john@example.com"
                            required 
                        />
                    </div>
                    <div className="space-y-2">
                        <Label htmlFor="password">Password (optional)</Label>
                        <Input 
                            id="password" 
                            type="password"
                            value={password} 
                            onChange={(e) => setPassword(e.target.value)} 
                            placeholder="Leave blank for no password"
                        />
                    </div>
                    <Button type="submit" disabled={createUser.isPending} className="w-full">
                        {createUser.isPending ? 'Creating...' : 'Save User'}
                    </Button>
                </form>
            </DialogContent>
        </Dialog>
    );
}

