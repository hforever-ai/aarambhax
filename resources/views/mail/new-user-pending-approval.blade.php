<x-mail::message>
# New registration awaiting approval

A new advocate has registered and is waiting for admin approval before they can use Aarambh Legal.

**Name:** {{ $user->name }}
**Email:** {{ $user->email }}
**Registered:** {{ $user->created_at->format('d M Y, H:i') }}

Open the admin panel to approve or reject:

<x-mail::button :url="$approvalUrl" color="primary">
Open admin panel
</x-mail::button>

Approval is one-click — they'll be unable to use any feature until approved.

Thanks,<br>
Aarambh Legal
</x-mail::message>
