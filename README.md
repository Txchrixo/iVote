<p align="center">
  <img src="resources/img/ivote-red.png" alt="IVote" width="200">
</p>

<h3 align="center">Design and Implementation of an Electronic Voting System<br>Based on Blockchain and Cryptographic Techniques</h3>

<p align="center">
  <img src="docs/assets/cameroon.svg" alt="Flag of Cameroon" width="21" height="14">&nbsp; Bachelor's thesis · IUT de Douala, University of Douala, Cameroon · 2023<br>
  <sub>Proof of concept: Bitcoin testnet only, not meant for binding elections.</sub>
</p>

<p align="center">
  <img src="https://img.shields.io/badge/status-proof%20of%20concept-orange" alt="Proof of concept">
  <img src="https://img.shields.io/badge/PHP-8.1%20%E2%86%92%208.2%2B-777bb4" alt="PHP 8.1, upgraded to 8.2+">
  <img src="https://img.shields.io/badge/Laravel-10%20%E2%86%92%2012-ff2d20" alt="Laravel 10, upgraded to 12">
  <img src="https://img.shields.io/badge/Bitcoin-testnet-f7931a" alt="Bitcoin testnet">
  <img src="https://img.shields.io/badge/anonymity-ring%20signatures-7c5aed" alt="Ring signatures">
  <img src="https://img.shields.io/badge/license-GPL--3.0-blue" alt="GPL-3.0">
  <img src="https://img.shields.io/badge/DOI-pending-lightgrey" alt="DOI pending">
</p>

<p align="center">
  <a href="#abstract">Abstract</a> ·
  <a href="#adapting-the-design-to-cameroon">Cameroon</a> ·
  <a href="#protocol">Protocol</a> ·
  <a href="#cryptography">Cryptography</a> ·
  <a href="#evaluation">Evaluation</a> ·
  <a href="#election-walkthrough">Walkthrough</a> ·
  <a href="#design">Design</a> ·
  <a href="#related-work">Related work</a> ·
  <a href="#getting-started">Getting started</a> ·
  <a href="#citation">Citation</a>
</p>

---

| | |
|---|---|
| **Author** | NANA TCHOUNKEU Thierry Christian |
| **Academic supervisor** | [Dr. Jean de Dieu NKAPKOP](https://orcid.org/0000-0001-7736-5145), University of Douala |
| **Professional supervisor** | Mr. DJIMELI Christian |
| **Degree** | Licence de Technologie, Software Engineering |
| **Institution** | Institut Universitaire de Technologie (IUT), University of Douala |
| **Host company** | CODEC group, Douala |
| **Year** | 2023 |
| **Thesis** | Zenodo, DOI to be added on publication |
| **Defense slides** | [PDF, 41 slides](docs/slides/ivote-defense-2023.pdf) |

## Abstract

Electronic voting speeds up tabulation and widens access, but only earns trust if ballots stay
secret and anyone can check the result. IVote combines the **Bitcoin blockchain** with
**linkable ring signatures** [3]: each vote is a testnet transaction whose `OP_RETURN` commits
to a ring signature over the chosen candidate. The signature proves the voter belongs to the
registered electorate without revealing who they are, its key image exposes a second ballot from
the same voter, and the public ledger lets every voter confirm their ballot was counted
unchanged, while organisers can neither alter results nor see voters' identities.

The system was modelled with UML following the 2TUP process and built with PHP, Laravel
and MySQL. It extends the BlockVotes design [8] with remote identity verification (Veriff),
two-factor authentication for the authorities and an auditable election life cycle, and
adapts it to the Cameroonian electoral context.

<p align="center">
  <img src="docs/slides/02-idea.jpg" alt="E-voting + Blockchain + Ring signature = IVote" width="720">
</p>

## Adapting the design to Cameroon

<img src="docs/assets/cameroon.svg" alt="Flag of Cameroon" width="60" align="right">

BlockVotes [8] was designed for a university setting with pre-issued ballot codes. IVote keeps its
cryptographic core and reshapes the surrounding process around how elections are organised
in Cameroon:

| Cameroonian reality | What IVote does |
|---|---|
| Elections are organised by an independent body, Elections Cameroon (ELECAM) | The Election Authority plays that role; registration is handed to a separate Registration Authority so that no single party holds both voters' identities and their ballots |
| Identity rests on the national identity card (CNI), passport or residence permit | Remote identity check with Veriff on these Cameroonian documents, and a CNI field for candidates |
| Most citizens are online through a smartphone rather than a computer | Veriff hands the check over to the voter's phone by QR code or SMS |
| English and French are both official languages | The whole interface is bilingual, with a language switch on every page |
| Disputed results erode trust in the outcome | Every ballot is a public Bitcoin transaction that any citizen, observer or party can recount |

The 2023 demonstration simulated a municipal election. This remains a proof of concept: it does
not claim compliance with the Cameroonian electoral code and was not endorsed by ELECAM.

## Protocol

Three actors take part: the **Election Authority** (EA), the **Registration Authority** (RA)
and the **voter** V<sub>i</sub>.

| Notation | Meaning |
|---|---|
| G, q | generator and order of secp256k1 |
| x<sub>i</sub>, P<sub>i</sub> = x<sub>i</sub>G | voter key pair, generated in the browser; x<sub>i</sub> never leaves it |
| R = (P<sub>1</sub>, …, P<sub>n</sub>) | ring: every registered public key of the election, frozen when registration closes |
| H<sub>p</sub> | hash to curve (RFC 9380 [16]) |
| I<sub>i</sub> = x<sub>i</sub>H<sub>p</sub>(P<sub>i</sub>) | key image: identical in every signature made with x<sub>i</sub>, unlinkable to P<sub>i</sub> |
| m = (L, C) | ballot: election identifier L and candidate C |
| σ = (c<sub>0</sub>, s<sub>1</sub>…s<sub>n</sub>, I) | LSAG signature of m by some member of R |
| A | one-time Bitcoin testnet address taken from the EA's funded pool |

1. **Setup.** The EA creates the election and its Bitcoin address; the RA registers the candidates.
2. **Identification.** V<sub>i</sub> proves their identity with Veriff and receives a one-time link by email.
3. **Key registration.** V<sub>i</sub> generates (x<sub>i</sub>, P<sub>i</sub>) and registers P<sub>i</sub>. When registration closes, R is published.
4. **Funding.** The EA funds a pool of n + δ addresses in batched transactions, with no tag that could tie an address to a voter.
5. **Vote.** V<sub>i</sub> computes σ = Sign(m, R, x<sub>i</sub>) in the browser, takes any address A from the pool without identifying, publishes σ on the bulletin board and sends A's balance to the EA address with `OP_RETURN = H(σ) ‖ I ‖ C ‖ L` (68 bytes).
6. **Tally.** Anyone reads the transactions to the EA address, fetches each σ, checks H(σ) and Verify(m, R, σ), and counts, for each key image I, only the first valid ballot in block order.
7. **Verification.** V<sub>i</sub> finds their own transaction (individual verifiability); anyone can redo step 6 (universal verifiability).

| Property | Mechanism | Status |
|---|---|---|
| Eligibility | Veriff identity check; only keys in R can produce a valid σ | implemented |
| Unforgeability | LSAG, secure under the discrete logarithm assumption in the random oracle model [3] | implemented, tested |
| One voter, one vote | key image I; repeated I are discarded at the tally | implemented, tested |
| Ballot privacy | signer ambiguity among all n keys of R [3]; pool addresses are not tied to voters | implemented, tested |
| Integrity | ballots are Bitcoin transactions, immutable once confirmed | 2023 prototype |
| Individual and universal verifiability | public ballots, public ring, deterministic tally rule | 2023 prototype |
| Authority accountability | TOTP 2FA, role gates, audit log, staged life cycle | implemented |
| Coercion resistance | not addressed: a voter can prove their vote by revealing x<sub>i</sub> | out of scope |

"Implemented, tested" refers to [`resources/js/crypto/lsag.js`](resources/js/crypto/lsag.js) and its test suite;
wiring it into the ballot page in place of the 2023 code is the next step.

## Cryptography

### From the 2023 prototype to LSAG

Reviewing the prototype for this repository showed that its ring signature, inherited from
BlockVotes, **could be forged with public keys only**. It used the Rivest–Shamir–Tauman
construction [2] with RSA-1024 keys but a common domain of `L = 1024` bits. RST require
`L ≥ |n| + 160`, because their extended trapdoor `g` is the identity on
`[⌊(2^L−1)/n⌋·n, 2^L)`; with `L = |n|` that zone is large, and a forger closes the ring there
without inverting RSA. The attack succeeded in 20 out of 20 runs
([`research/forge-2023-ring-signature.mjs`](research/forge-2023-ring-signature.mjs)).

| | 2023 prototype | Current scheme |
|---|---|---|
| Scheme | RST ring signature [2] | LSAG linkable ring signature [3] |
| Keys | RSA-1024, generated with JSEncrypt | secp256k1, the curve of Bitcoin |
| Hash | SHA-1 | SHA-256, domain-separated; RFC 9380 hash to curve |
| Randomness | RC4-based PRNG seeded once | platform CSPRNG for every scalar |
| Ring bound to the signature | no, keys travel inside σ | yes, the ring digest enters every challenge |
| Double vote | detected only by counting one ballot per address | detected by the key image, whatever the address |
| Forgery without a private key | **possible** (20/20) | none known; off-curve points and out-of-range scalars are rejected |
| Signature size | ≈ 640 B per ring member | 32 B per ring member + 65 B |

The implementation relies on the audited `@noble/curves` and `@noble/hashes` libraries and is
covered by tests for correctness, binding to the ballot and to the ring, tampering, invalid
encodings, linkability and randomisation ([`tests/js/lsag.test.mjs`](tests/js/lsag.test.mjs)).

### Threat model

| Adversary | Can | Cannot |
|---|---|---|
| Public observer | read every ballot and recount | tell which registered voter cast a ballot |
| Curious EA | see ballots, refuse to fund or serve addresses (visibly) | forge or alter ballots, link a ballot to a voter |
| Curious RA, Veriff | link a person to P<sub>i</sub> | link P<sub>i</sub> to a ballot (the key image hides it) |
| Dishonest voter | vote once, abstain, reveal their own vote | vote twice, vote outside the ring |
| Network observer | link the IP that fetched an address or published σ | anything more, if voters use an anonymity network such as Tor |

Assumptions: the EA and the RA do not collude; voters' browsers are not compromised; the
discrete logarithm problem on secp256k1 is hard; the Bitcoin network keeps a single honest
history. The bulletin board must keep every σ available; since each H(σ) is on-chain, a
withheld or altered σ is detected, not hidden.

## Evaluation

Timings are medians of 5 runs on an Intel Core i7-10610U laptop with Node.js, produced by the
scripts in [`research/`](research/). Transaction sizes follow the Bitcoin serialisation format.

### Ring signatures

| Ring size n | 2023 sign | 2023 verify | 2023 size | LSAG sign | LSAG verify | LSAG size |
|---:|---:|---:|---:|---:|---:|---:|
| 2 | 118 ms | 21 ms | 1.3 KB | 50 ms | 42 ms | 129 B |
| 8 | 199 ms | 53 ms | 5.2 KB | 133 ms | 137 ms | 321 B |
| 32 | 742 ms | 279 ms | 20.6 KB | 539 ms | 516 ms | 1.1 KB |
| 64 | 1.22 s | 437 ms | 41.1 KB | 1.03 s | 1.04 s | 2.1 KB |
| 128 | 1.68 s | 740 ms | 82.0 KB | 2.71 s | 2.94 s | 4.2 KB |

LSAG costs about 22 ms per ring member to sign or verify, linear in n, and its signatures are
20 times smaller. The 2023 scheme verifies faster (RSA with e = 65537) but is forgeable. A
tally checks every ballot against the same ring, so n ballots cost O(n²) operations, about
6 minutes for a ring of 128; rings of a few hundred voters, for example one per polling
station, are the practical size.

### On-chain cost

| Transaction | Size | Fee at 2 sat/vB | at 10 sat/vB | at 50 sat/vB |
|---|---:|---:|---:|---:|
| Ballot: 1 P2PKH input, `OP_RETURN` of 68 B, 1 P2PKH output | 271 B | 542 sat | 2,710 sat | 13,550 sat |
| Funding: each pool address in a batched transaction | 34 B | 68 sat | 340 sat | 1,700 sat |

The 2023 code paid a fixed fee of 0.01 BTC per ballot, about 4,000 sat/vB for its 249-byte
transaction. A ballot is recorded once its transaction is mined, about 10 minutes on average,
and is commonly treated as final after 6 confirmations, about one hour; these are properties
of Bitcoin, not measurements of IVote.

## Election walkthrough

An election moves through five stages: **Initial → Identity check → Waiting → Voting → Closed**.
Screens below come from the demo data set (`DemoSeeder`); wallet balances are mocked since the demo has no funded wallets.

### Authorities

<table>
  <tr>
    <td width="33%"><img src="docs/screenshots/01-login.jpg" alt="Sign in"></td>
    <td width="33%"><img src="docs/screenshots/02-two-factor.jpg" alt="Two-factor authentication"></td>
    <td width="33%"><img src="docs/screenshots/03-ea-dashboard.jpg" alt="Authority dashboard"></td>
  </tr>
  <tr>
    <td align="center"><b>1.</b> Back-office sign-in</td>
    <td align="center"><b>1.</b> TOTP second factor</td>
    <td align="center"><b>1.</b> EA dashboard, three chains</td>
  </tr>
  <tr>
    <td><img src="docs/screenshots/04-ea-wallet.jpg" alt="Wallet settings"></td>
    <td><img src="docs/screenshots/05-ea-create-election.jpg" alt="Create an election"></td>
    <td><img src="docs/screenshots/06-ea-elections.jpg" alt="Election list"></td>
  </tr>
  <tr>
    <td align="center"><b>1.</b> EA testnet wallet</td>
    <td align="center"><b>1.</b> New election</td>
    <td align="center"><b>1.</b> Elections and their stage</td>
  </tr>
  <tr>
    <td><img src="docs/screenshots/09-ra-dashboard.jpg" alt="Registration dashboard"></td>
    <td><img src="docs/screenshots/08-ra-add-candidate.jpg" alt="Add a candidate"></td>
    <td><img src="docs/screenshots/07-ra-candidates.jpg" alt="Candidates"></td>
  </tr>
  <tr>
    <td align="center"><b>1.</b> RA dashboard</td>
    <td align="center"><b>1.</b> New candidate</td>
    <td align="center"><b>1.</b> Candidates per election</td>
  </tr>
  <tr>
    <td><img src="docs/screenshots/12-ra-ballot-codes.jpg" alt="Ballot codes"></td>
    <td><img src="docs/screenshots/16-ea-fund-addresses.jpg" alt="Fund voter addresses"></td>
    <td><img src="docs/screenshots/17-ea-balances.jpg" alt="Address balances"></td>
  </tr>
  <tr>
    <td align="center"><b>2.</b> Personal ballot codes</td>
    <td align="center"><b>4.</b> Funding voter addresses</td>
    <td align="center"><b>4.</b> Address balances</td>
  </tr>
</table>

### Identity verification with Veriff

<table>
  <tr>
    <td width="33%"><img src="docs/screenshots/10-voter-veriff.jpg" alt="Identity check page"></td>
    <td width="33%"><img src="docs/screenshots/2023/veriff-start.jpg" alt="Veriff session start"></td>
    <td width="33%"><img src="docs/screenshots/2023/veriff-handoff.jpg" alt="Continue on a phone"></td>
  </tr>
  <tr>
    <td align="center"><b>2.</b> Identity check page</td>
    <td align="center"><b>2.</b> Starting a Veriff session</td>
    <td align="center"><b>2.</b> Continue on a phone</td>
  </tr>
  <tr>
    <td><img src="docs/screenshots/2023/veriff-sms-sent.jpg" alt="Secure link sent"></td>
    <td><img src="docs/screenshots/2023/veriff-mobile-document.jpg" alt="Document selection"></td>
    <td><img src="docs/screenshots/2023/veriff-mobile-capture.jpg" alt="Document photo and selfie"></td>
  </tr>
  <tr>
    <td align="center"><b>2.</b> Secure link sent by SMS</td>
    <td align="center"><b>2.</b> Choosing the ID document</td>
    <td align="center"><b>2.</b> Document photo, selfie, done</td>
  </tr>
  <tr>
    <td><img src="docs/screenshots/11-voter-veriff-submitted.jpg" alt="Verification submitted"></td>
    <td><img src="docs/screenshots/2023/voter-email.jpg" alt="Approval email"></td>
    <td><img src="docs/screenshots/13-voter-ready.jpg" alt="Ready to vote"></td>
  </tr>
  <tr>
    <td align="center"><b>2.</b> Check submitted</td>
    <td align="center"><b>2.</b> Approval email with the voter link</td>
    <td align="center"><b>3.</b> Voter link opened</td>
  </tr>
</table>

### Keys, vote, tally and verification

<table>
  <tr>
    <td width="50%"><img src="docs/screenshots/14-voter-generate-keys.jpg" alt="Key generation"></td>
    <td width="50%"><img src="docs/screenshots/15-voter-wait.jpg" alt="Waiting for the vote"></td>
  </tr>
  <tr>
    <td align="center"><b>3.</b> Key pair generated in the browser</td>
    <td align="center"><b>3.</b> Waiting for the voting day</td>
  </tr>
  <tr>
    <td><img src="docs/screenshots/2023/wait-and-vote.jpg" alt="Ballot and voter identity"></td>
    <td><img src="docs/screenshots/2023/sign-verify-results.jpg" alt="Signing, verification and results"></td>
  </tr>
  <tr>
    <td align="center"><b>5.</b> Ballot and voter identity (2023 prototype)</td>
    <td align="center"><b>5–7.</b> Ring signature, verification, results (2023 prototype)</td>
  </tr>
</table>

The last two captures come from the 2023 prototype (French UI); these steps are being ported to the current Laravel version.

## Design

The analysis follows the 2TUP process with UML. Click a diagram to open it full size.

<table>
  <tr>
    <td width="50%"><img src="docs/diagrams/use-cases.jpg" alt="Use case diagram"></td>
    <td width="50%"><img src="docs/diagrams/class-diagram.jpg" alt="Class diagram"></td>
  </tr>
  <tr>
    <td align="center">Use cases</td>
    <td align="center">Classes</td>
  </tr>
  <tr>
    <td><img src="docs/diagrams/sequence-identification.jpg" alt="Identification sequence"></td>
    <td><img src="docs/diagrams/sequence-vote.jpg" alt="Vote sequence"></td>
  </tr>
  <tr>
    <td align="center">Sequence: identification with Veriff</td>
    <td align="center">Sequence: casting a vote</td>
  </tr>
  <tr>
    <td><img src="docs/diagrams/sequence-verification.jpg" alt="Verification sequence"></td>
    <td><img src="docs/diagrams/sequence-authentication.jpg" alt="Authentication sequence"></td>
  </tr>
  <tr>
    <td align="center">Sequence: verifying a vote</td>
    <td align="center">Sequence: authority sign-in</td>
  </tr>
  <tr>
    <td colspan="2" align="center"><img src="docs/diagrams/deployment.jpg" alt="Deployment diagram" width="528"></td>
  </tr>
  <tr>
    <td colspan="2" align="center">Deployment (2023): WampServer, the IVote app, the voter's phone for Veriff, SoChain and Bitcoin testnet</td>
  </tr>
</table>

<details>
<summary><b>Project timeline (18 weeks)</b></summary>
<br>
<img src="docs/diagrams/gantt.jpg" alt="Gantt chart">
</details>

<details>
<summary><b>Defense slides</b></summary>
<br>
<table>
  <tr>
    <td width="33%"><img src="docs/slides/01-title.jpg" alt="Title slide"></td>
    <td width="33%"><img src="docs/slides/03-identification.jpg" alt="Identification protocol slide"></td>
    <td width="33%"><img src="docs/slides/04-vote.jpg" alt="Vote protocol slide"></td>
  </tr>
</table>
<p>Full deck: <a href="docs/slides/ivote-defense-2023.pdf">ivote-defense-2023.pdf</a> (GitHub shows it in the browser).</p>
</details>

## Architecture

```
 Voter browser ── key pair, ring signature (private key stays here)
        │
        ▼
 Laravel app ── MySQL (elections, candidates, public keys, audit log)
        │
        ├── Veriff ............ identity verification (document + selfie)
        ├── Block.io .......... funding voter addresses, OP_RETURN on Bitcoin testnet
        ├── CryptoAPIs ........ EA balance on Bitcoin testnet and Ethereum Goerli
        └── Solana RPC ........ EA balance on Solana devnet
```

### Stack

The thesis version (July 2023) ran on WampServer. The code was later upgraded to the current
Laravel release and hardened; the protocol and the screens did not change.

| Layer | Thesis version (2023) | Current version |
|---|---|---|
| Back end | PHP 8.1, Laravel 10, MySQL 8 on WampServer (Apache) | PHP 8.2+, Laravel 12, MySQL 8 |
| Front end | Blade, Bootstrap 5.3, jQuery 3.7, Vite 4, Chartist | same, Vite 6 |
| Blockchain | Bitcoin testnet (`OP_RETURN`), BitcoinECDSA, BitcoinJS, Block.io, SoChain API | Block.io, CryptoAPIs (SoChain's free API was retired), Ethereum and Solana balances |
| Cryptography | RST ring signatures, RSA-1024 voter keys, secp256k1 Bitcoin keys | LSAG on secp256k1 (`@noble/curves`), authority keys encrypted at rest (AES-256) |
| Identity and access | Veriff, password sign-in for the authorities | Veriff, TOTP 2FA, role gates, CSP with nonces, rate limits, audit log |
| Method | 2TUP, UML (Visual Paradigm), Figma mock-ups | same, automated feature tests |

<p align="center"><img src="docs/diagrams/tools.jpg" alt="Tools used" width="640"></p>

## Related work

IVote sits in the line of work that keeps ballots anonymous with ring signatures while using a
public blockchain as the bulletin board.

| Work | Year | Ledger | Anonymity | Tally | Identity |
|---|---|---|---|---|---|
| Zhao & Chan [6] | 2015 | Bitcoin | zero-knowledge proofs | on-chain deposits | not covered |
| Open Vote Network [7] | 2017 | Ethereum | self-tallying, ZK proofs | smart contract | registered voters |
| BlockVotes [8] | 2017 | Bitcoin | ring signatures | public, off-chain | ballot codes |
| Hjálmarsson et al. [9] | 2018 | permissioned chain | trusted authority | smart contract | trusted authority |
| Yu et al. [10] | 2018 | Hyperledger Fabric | linkable ring signatures | homomorphic | registered voters |
| Chirotonia [11] | 2021 | Ethereum | linkable ring signatures | smart contract | registered voters |
| **IVote** | 2023 | Bitcoin testnet | linkable ring signatures | public, off-chain | Veriff on Cameroonian ID + email link |

Surveys [12–15] cover the wider landscape and its open problems (scalability, coercion, usability).

## Getting started

Requirements: PHP 8.2+ with `gmp`, Composer, Node 18+, MySQL 8.

```bash
git clone https://github.com/Txchrixo/iVote.git && cd iVote
composer install && npm install
cp .env.example .env && php artisan key:generate
php artisan migrate --seed    # creates the EA and RA accounts, prints their passwords
npm run build && php artisan serve
```

Set `BLOCKIO_*`, `CRYPTOAPIS_API_KEY` and `VERIFF_*` in `.env` to reach the external services.

**Demo data.** To explore a full election season (five elections, candidates, ballot codes and
testnet addresses) on an empty database:

```bash
php artisan migrate:fresh --seed --seeder=DemoSeeder
```

Accounts `ea` and `ra` share a password and TOTP secret, both printed once
(or set through `DEMO_PASSWORD` and `DEMO_2FA_SECRET`). The seeder refuses to run in production.

## Tests

```bash
php artisan test     # back end
npm run test:js      # linkable ring signature
```

Feature tests cover authorization, two-factor sign-in, election stages, candidates, blockchain
balances, security headers, the audit log and the public results pages.

## Repository layout

```
app/          controllers, models, election stages, security middleware
database/     migrations and seeders (including DemoSeeder)
resources/    Blade views, styles, page scripts, LSAG module (js/crypto)
routes/       web routes (back office, voter flow, results)
tests/        feature tests (PHP) and ring signature tests (JS)
research/     forgery of the 2023 scheme, benchmarks
docs/         screenshots, UML diagrams, defense slides
```

## Limitations and future work

- This is a proof of concept on Bitcoin testnet; it has not been audited and must not run a binding election.
- One voter could still receive several funded addresses; the address pool needs a binding to the registered public key.
- Tally cost grows with n² for one ring; large elections need one ring per polling station, which shrinks each anonymity set.
- No coercion resistance: a voter can prove how they voted.
- Transaction timing and amounts may allow linkability; batching or mixing would reduce it.
- Bitcoin fees and confirmation times do not scale to national elections.
- Voting, tally and verification run in the 2023 prototype and are being ported to the current Laravel version.

## Citation

If you build on this work, please cite the thesis. GitHub also offers a citation from
[`CITATION.cff`](CITATION.cff).

```bibtex
@thesis{nana2023ivote,
  author      = {Nana Tchounkeu, Thierry Christian},
  title       = {Design and Implementation of an Electronic Voting System Based on Blockchain and Cryptographic Techniques},
  type        = {Bachelor's thesis},
  institution = {Institut Universitaire de Technologie, University of Douala},
  year        = {2023}
}
```

## References

1. S. Nakamoto. *Bitcoin: A Peer-to-Peer Electronic Cash System*. 2008.
2. R. L. Rivest, A. Shamir, Y. Tauman. *How to Leak a Secret*. ASIACRYPT 2001, LNCS 2248, pp. 552–565. [doi:10.1007/3-540-45682-1_32](https://doi.org/10.1007/3-540-45682-1_32)
3. J. K. Liu, V. K. Wei, D. S. Wong. *Linkable Spontaneous Anonymous Group Signature for Ad Hoc Groups*. ACISP 2004, LNCS 3108.
4. B. Adida. *Helios: Web-based Open-Audit Voting*. USENIX Security Symposium, 2008.
5. F. Hao, P. Y. A. Ryan, P. Zieliński. *Anonymous Voting by Two-Round Public Discussion*. IET Information Security 4(2), 2010.
6. Z. Zhao, T.-H. H. Chan. *How to Vote Privately Using Bitcoin*. ICICS 2015, LNCS 9543, pp. 82–96. [ePrint 2015/1007](https://eprint.iacr.org/2015/1007)
7. P. McCorry, S. F. Shahandashti, F. Hao. *A Smart Contract for Boardroom Voting with Maximum Voter Privacy*. Financial Cryptography 2017. [doi:10.1007/978-3-319-70972-7_20](https://doi.org/10.1007/978-3-319-70972-7_20)
8. Y. Wu. *An E-voting System based on Blockchain and Ring Signature* (BlockVotes). MSc thesis, University of Birmingham, 2017. [github.com/yfgeek/BlockVotes](https://github.com/yfgeek/BlockVotes)
9. F. Þ. Hjálmarsson, G. K. Hreiðarsson, M. Hamdaqa, G. Hjálmtýsson. *Blockchain-Based E-Voting System*. IEEE CLOUD 2018, pp. 983–986. [doi:10.1109/CLOUD.2018.00151](https://doi.org/10.1109/CLOUD.2018.00151)
10. B. Yu, J. K. Liu, A. Sakzad, S. Nepal, R. Steinfeld, P. Rimba, M. H. Au. *Platform-Independent Secure Blockchain-Based Voting System*. ISC 2018. [doi:10.1007/978-3-319-99136-8_20](https://doi.org/10.1007/978-3-319-99136-8_20)
11. A. Russo, A. Fernández Anta, M. I. González Vasco, S. P. Romano. *Chirotonia: A Scalable and Secure e-Voting Framework based on Blockchains and Linkable Ring Signatures*. IEEE Blockchain 2021, pp. 417–424. [arXiv:2111.02257](https://arxiv.org/abs/2111.02257)
12. J. Huang, D. He, M. S. Obaidat, P. Vijayakumar, M. Luo, K.-K. R. Choo. *The Application of the Blockchain Technology in Voting Systems: A Review*. ACM Computing Surveys 54(3), 2021.
13. M. Benabdallah, A. Audras, L. Coudert, N. El Madhoun, M. Badra. *Analysis of Blockchain Solutions for E-Voting: A Systematic Literature Review*. IEEE Access 10, 2022. [doi:10.1109/ACCESS.2022.3187688](https://doi.org/10.1109/ACCESS.2022.3187688)
14. U. Jafar, M. J. Ab Aziz, Z. Shukur, H. A. Hussain. *A Systematic Literature Review and Meta-Analysis on Scalable Blockchain-Based Electronic Voting Systems*. Sensors 22(19), 2022. [doi:10.3390/s22197585](https://doi.org/10.3390/s22197585)
15. V. Vladucu, Z. Dong, J. Medina, R. Rojas-Cessa. *E-Voting Meets Blockchain: A Survey*. IEEE Access 11, pp. 23293–23308, 2023. [doi:10.1109/ACCESS.2023.3253682](https://doi.org/10.1109/ACCESS.2023.3253682)
16. A. Faz-Hernández, S. Scott, N. Sullivan, R. S. Wahby, C. A. Wood. *Hashing to Elliptic Curves*. RFC 9380, IETF, 2023. [doi:10.17487/RFC9380](https://doi.org/10.17487/RFC9380)

## Acknowledgments

[Dr. Jean de Dieu NKAPKOP](https://orcid.org/0000-0001-7736-5145) for the academic supervision, Mr. DJIMELI Christian and the CODEC team for
the internship and their blockchain expertise. IVote started from Yifan Wu's BlockVotes [8]
and relies on the BitcoinECDSA.php library.

## License

[GPL-3.0](LICENSE), inherited from BlockVotes [8].
