<x-mail::message>
# Welcome to Aarambh Legal, {{ explode(' ', $user->name)[0] }}!

Your account has been approved. You can now use all features — case briefs, timelines, samjhao, drafts, and the full chambers AI workflow.

<x-mail::button :url="$loginUrl" color="primary">
Log in to Aarambh Legal
</x-mail::button>

If you have questions, just reply to this email — `admin@aarambhax.in` reaches us directly.

Welcome aboard,<br>
Aarambh Legal
</x-mail::message>
