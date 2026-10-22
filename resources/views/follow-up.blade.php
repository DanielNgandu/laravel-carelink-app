<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>CareLink Follow-Up</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    
</head>
<body class="min-h-screen bg-slate-100 text-slate-900">
<main class="mx-auto max-w-7xl p-4 sm:p-8">
    <header class="mb-6">
        <h1 class="text-3xl font-bold">CareLink Follow-Up</h1>
        <p class="text-slate-600">Facility appointment follow-up. Synthetic assessment data only.</p>
        <div class="flex items-center gap-4">
        <span class="text-sm text-gray-700">
            {{ auth()->user()->name }}
        </span>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit"
                class="rounded-lg bg-gray-800 px-4 py-2 text-white hover:bg-gray-900">
                Logout
            </button>
        </form>
    </div>
    </header>

    <section class="rounded-xl bg-white p-5 shadow-sm" aria-labelledby="filter-heading">
        <h2 id="filter-heading" class="mb-4 text-xl font-semibold">Find patients</h2>
        <form id="filters" class="grid gap-4 md:grid-cols-3">
<div>
                <label for="facility" class="block font-medium">Facility ID</label>
                <input id="facility" type="text" value="{{ auth()->user()->role === 'CLINICIAN' ? auth()->user()->facility_id : '' }}" maxlength="30" class="mt-1 w-full rounded border border-slate-400 p-2" placeholder="FAC-0101">
            </div>
            <div>
                <label for="status" class="block font-medium">Follow-up status</label>
                <select id="status" class="mt-1 w-full rounded border border-slate-400 p-2">
                    <option value="overdue">Overdue</option>
                    <option value="missed">Recently missed</option>
                    <option value="due_soon">Due soon</option>
                </select>
            </div>
            <div>
                <label for="threshold" class="block font-medium">Overdue threshold (days)</label>
                <input id="threshold" type="number" min="1" max="365" value="7" required class="mt-1 w-full rounded border border-slate-400 p-2">
            </div>
            <div>
                <label for="sort" class="block font-medium">Sort</label>
                <select id="sort" class="mt-1 w-full rounded border border-slate-400 p-2">
                    <option value="days_overdue_desc">Most days overdue first</option>
                    <option value="days_overdue_asc">Fewest days overdue first</option>
                </select>
            </div>
            <div>
                <label for="pageSize" class="block font-medium">Rows per page</label>
                <select id="pageSize" class="mt-1 w-full rounded border border-slate-400 p-2">
                    <option value="25">25</option>
                    <option value="50" selected>50</option>
                    <option value="100">100</option>
                </select>
            </div>
            <div class="md:col-span-3">
                <button type="submit" class="rounded bg-blue-800 px-5 py-2 font-semibold text-white hover:bg-blue-900 focus:outline focus:outline-2 focus:outline-offset-2 focus:outline-blue-800">Search</button>
            </div>
        </form>
    </section>

    <section class="mt-6 rounded-xl bg-white p-5 shadow-sm" aria-labelledby="results-heading">
        <div class="mb-4 flex flex-wrap items-center justify-between gap-2">
            <h2 id="results-heading" class="text-xl font-semibold">Patients requiring follow-up</h2>
            <p id="summary" class="text-sm text-slate-600"></p>
        </div>
        <p id="message" role="status" aria-live="polite" class="mb-3"></p>
        <div id="errorPanel" role="alert" class="mb-4 hidden rounded border border-red-500 bg-red-50 p-3 text-red-900">
            <span id="errorText"></span>
            <button id="retry" type="button" class="ml-3 rounded border border-red-600 px-3 py-1 font-semibold">Retry</button>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full min-w-[800px] border-collapse text-left text-sm">
                <caption class="sr-only">Patients with appointments requiring follow-up</caption>
                <thead class="bg-slate-100">
                <tr>
                    <th scope="col" class="p-3">Patient</th>
                    <th scope="col" class="p-3">Facility</th>
                    <th scope="col" class="p-3">Appointment</th>
                    <th scope="col" class="p-3">Days overdue</th>
                    <th scope="col" class="p-3">Status</th>
                    <th scope="col" class="p-3">Last contact</th>
                    <th scope="col" class="p-3">Action</th>
                </tr>
                </thead>
                <tbody id="results"></tbody>
            </table>
        </div>
        <div class="mt-5 flex items-center gap-3">
            <button id="previous" type="button" class="rounded border border-slate-400 px-4 py-2 disabled:opacity-40">Previous</button>
            <span id="pageLabel" aria-live="polite">Page 1</span>
            <button id="next" type="button" class="rounded border border-slate-400 px-4 py-2 disabled:opacity-40">Next</button>
        </div>
    </section>
</main>

<dialog id="contactDialog" aria-labelledby="contactTitle" class="m-auto w-full max-w-md rounded-xl p-6 shadow-xl backdrop:bg-black/40">
    <h2 id="contactTitle" class="mb-3 text-xl font-bold">Record a contact attempt</h2>
    <p id="selectedPatient" class="mb-4"></p>
    <form id="contactForm">
        <label for="note" class="block font-medium">Contact note</label>
        <textarea id="note" rows="4" maxlength="1000" required class="mt-1 w-full rounded border border-slate-400 p-2"></textarea>
        <p id="contactError" role="alert" class="mt-2 text-red-700"></p>
        <div class="mt-4 flex gap-3">
            <button type="submit" id="saveContact" class="rounded bg-blue-800 px-4 py-2 text-white">Save contact</button>
            <button type="button" id="cancelContact" class="rounded border px-4 py-2">Cancel</button>
        </div>
    </form>
</dialog>

<script>
(() => {
    const $ = (id) => document.getElementById(id);
    const state = { page: 1, total: 0, items: [], loading: false, selectedPatient: null, operation: null };
    const apiUrl = '/follow-up';
    const currentUser = {
    role: @json(auth()->user()->role),
    facility_id: @json(auth()->user()->facility_id)
};

const canRecordContact = currentUser.role === 'CLINICIAN';

    const td = (value) => { const cell = document.createElement('td'); cell.className = 'border-b border-slate-200 p-3'; cell.textContent = String(value ?? 'Not recorded'); return cell; };
    const showError = (text) => { $('errorText').textContent = text; $('errorPanel').classList.remove('hidden'); };
    const clearError = () => { $('errorText').textContent = ''; $('errorPanel').classList.add('hidden'); };
    function updatePager() {
        const maxPage = Math.max(1, Math.ceil(state.total / Number($('pageSize').value)));
        $('pageLabel').textContent = `Page ${state.page} of ${maxPage}`;
        $('previous').disabled = state.loading || state.page <= 1;
        $('next').disabled = state.loading || state.page >= maxPage;
    }
    
    function render() {
        const tbody = $('results');
        tbody.replaceChildren();
        state.items.forEach((patient) => {
            const row = document.createElement('tr');
            row.append(td(`${patient.first_name} ${patient.last_name} (${patient.patient_number})`));
            row.append(td(patient.facility_name));
            row.append(td(patient.missed_appointment_date));
            row.append(td(patient.days_overdue));
            row.append(td(patient.follow_up_status.replaceAll('_', ' ')));
            row.append(td(patient.last_contact_attempt));
            const actions = document.createElement('td');
            actions.className = 'border-b border-slate-200 p-3';
            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'rounded border border-blue-700 px-3 py-1 text-blue-800 hover:bg-blue-50 disabled:opacity-40';
            button.textContent = 'Record contact';
            button.disabled = !canRecordContact;
            button.addEventListener('click', () => openContact(patient));
            actions.append(button);
            row.append(actions);
            tbody.append(row);
        });
        $('summary').textContent = `${state.total.toLocaleString()} matching patients`;
        if (!state.items.length) $('message').textContent = 'No patients match the selected filters.';
        updatePager();
    }
    
async function load() {
    if (state.loading) return;

    state.loading = true;
    clearError();

    $('message').textContent = 'Loading follow-up records...';
    $('results').replaceChildren();
    updatePager();

    const params = new URLSearchParams({
        status: $('status').value,
        overdue_days: $('threshold').value,
        sort: $('sort').value,
        page: String(state.page),
        page_size: $('pageSize').value
    });

    if (currentUser.role === 'CLINICIAN') {
        params.set('facility_id', currentUser.facility_id);
    } else if ($('facility').value.trim()) {
        params.set('facility_id', $('facility').value.trim());
    }

    try {
        const response = await fetch(
            `/follow-up/data?${params.toString()}`,
            {
                credentials: 'same-origin',
                headers: {
                    'Accept': 'application/json'
                },
                cache: 'no-store'
            }
        );

        if (response.status === 401 || response.redirected && response.url.includes('/login')) {
            window.location.href = '/login';
            return;
        }

        const body = await response.json();

        if (!response.ok) {
            throw new Error(
                body.error?.message ??
                body.message ??
                `Request failed (${response.status})`
            );
        }

        state.items = body.items;
        state.total = body.total;

        $('message').textContent = '';
        render();

    } catch (err) {
        state.items = [];
        state.total = 0;

        $('summary').textContent = '';
        $('message').textContent = '';

        showError(`Unable to load records: ${err.message}`);
    } finally {
        state.loading = false;
        updatePager();
    }
}

    function openContact(patient) {
        state.selectedPatient = patient;
        state.operation = null;
        $('selectedPatient').textContent = `${patient.first_name} ${patient.last_name}`;
        $('note').value = '';
        $('contactError').textContent = '';
        $('contactDialog').showModal();
        $('note').focus();
    }
    async function saveContact(event) {
        event.preventDefault();
        const note = $('note').value.trim();
        if (!note) { $('contactError').textContent = 'Enter a contact note.'; return; }
        // Keep the same UUID and payload on retry. A changed note is a new operation.
        if (!state.operation || state.operation.note !== note) {
            state.operation = { interaction_id: crypto.randomUUID(), contacted_at: new Date().toISOString(), note };
        }
        $('saveContact').disabled = true;
        $('contactError').textContent = '';
        try {
            const response = await fetch(
    `/follow-up/${encodeURIComponent(state.selectedPatient.id)}/contacted`,
    {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
            'Accept': 'application/json',
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector(
                'meta[name="csrf-token"]'
            ).content
        },
        body: JSON.stringify(state.operation)
    }
);
            if (response.status === 401 || response.status === 419) {
                throw new Error('Your session expired. Sign in again, then retry with a new contact attempt.');
            }
            if (!response.ok) {
                const body = await response.json().catch(() => ({}));
                throw new Error(body.error?.message ?? body.message ?? `Request failed (${response.status})`);
            }
            $('contactDialog').close();
            await load();
        } catch (err) { $('contactError').textContent = `Could not save contact: ${err.message}. You can retry.`; }
        finally { $('saveContact').disabled = false; }
    }
    $('filters').addEventListener('submit', (event) => { event.preventDefault(); state.page = 1; load(); });
    $('retry').addEventListener('click', load);
    $('previous').addEventListener('click', () => { if (state.page > 1) { state.page--; load(); } });
    $('next').addEventListener('click', () => { state.page++; load(); });
    $('contactForm').addEventListener('submit', saveContact);
    $('cancelContact').addEventListener('click', () => $('contactDialog').close());
    if (currentUser.role === 'CLINICIAN') {
    $('facility').value = currentUser.facility_id;
    $('facility').readOnly = true;
}
    load();
})();
</script>
</body>
</html>
