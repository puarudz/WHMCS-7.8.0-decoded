<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

/**
 * Pure-PHP implementation of Blowfish.
 *
 * Uses mcrypt, if available, and an internal implementation, otherwise.
 *
 * PHP version 5
 *
 * Useful resources are as follows:
 *
 *  - {@link http://en.wikipedia.org/wiki/Blowfish_(cipher) Wikipedia description of Blowfish}
 *
 * Here's a short example of how to use this library:
 * <code>
 * <?php
 *    include 'vendor/autoload.php';
 *
 *    $blowfish = new \phpseclib\Crypt\Blowfish();
 *
 *    $blowfish->setKey('12345678901234567890123456789012');
 *
 *    $plaintext = str_repeat('a', 1024);
 *
 *    echo $blowfish->decrypt($blowfish->encrypt($plaintext));
 * ?>
 * </code>
 *
 * @category  Crypt
 * @package   Blowfish
 * @author    Jim Wigginton <terrafrost@php.net>
 * @author    Hans-Juergen Petrich <petrich@tronic-media.com>
 * @copyright 2007 Jim Wigginton
 * @license   http://www.opensource.org/licenses/mit-license.html  MIT License
 * @link      http://phpseclib.sourceforge.net
 */
namespace phpseclib\Crypt;

/**
 * Pure-PHP implementation of Blowfish.
 *
 * @package Blowfish
 * @author  Jim Wigginton <terrafrost@php.net>
 * @author  Hans-Juergen Petrich <petrich@tronic-media.com>
 * @access  public
 */
class Blowfish extends Base
{
    /**
     * Block Length of the cipher
     *
     * @see \phpseclib\Crypt\Base::block_size
     * @var int
     * @access private
     */
    var $block_size = 8;
    /**
     * The mcrypt specific name of the cipher
     *
     * @see \phpseclib\Crypt\Base::cipher_name_mcrypt
     * @var string
     * @access private
     */
    var $cipher_name_mcrypt = 'blowfish';
    /**
     * Optimizing value while CFB-encrypting
     *
     * @see \phpseclib\Crypt\Base::cfb_init_len
     * @var int
     * @access private
     */
    var $cfb_init_len = 500;
    /**
     * The fixed subkeys boxes ($sbox0 - $sbox3) with 256 entries each
     *
     * S-Box 0
     *
     * @access private
     * @var    array
     */
    var $sbox0 = array(3509652390.0, 2564797868.0, 0x2ffd72db, 3491422135.0, 0.0, 0x6a267e96, 3128725573.0, 4046225305.0, 0x24a19947, 3012652279.0, 0x801f2e2, 0.0, 0x636920d8, 0x71574e69, 0.0, 0.0, 0xd95748f, 0x728eb658, 0x718bcd58, 0.0, 0x7b54a41d, 3260701109.0, 2620446009.0, 0x2af26013, 3318853667.0, 0x286085f0, 3393288472.0, 0.0, 0.0, 0x603a180e, 0x6c9e0e8b, 0.0, 3608508353.0, 3174124327.0, 0x78af2fda, 0x55605c60, 0.0, 2857741204.0, 0x57489862, 0x63e81440, 0x55ca396a, 0x2aab10b6, 3033291828.0, 0x1141e8ce, 2706671279.0, 0x7c72e993, 0.0, 0x636fbc2a, 0x2ba9c55d, 0x741831f6, 0.0, 0.0, 2950085171.0, 0x6c24cf5c, 0x7a325381, 0x28958677, 0x3b8f4898, 0x6b4bb9af, 0.0, 0x66282193, 0x61d809cc, 4213287313.0, 0x487cac60, 0x5dec8032, 0.0, 0.0, 3693486850.0, 0.0, 0x23893e81, 3549867205.0, 0xf6d6ff3, 2213823033.0, 0x2e0b4482, 2760122372.0, 0x69c8f04a, 0.0, 0x21c66842, 0.0, 0x670c9c61, 2882767088.0, 0x6a51a0d2, 3629395816.0, 2517608232.0, 2874225571.0, 0x6eef0b6c, 0x137a3be4, 3124490320.0, 0x7efb2a98, 2716951837.0, 0x39af0176, 0x66ca593e, 0.0, 0.0, 0x456f9fb4, 0x7d84a5c3, 0x3b8b5ebe, 0.0, 2244026483.0, 0x401a449f, 0x56c16aa6, 0x4ed3aa62, 0x363f7706, 0x1bfedf72, 0x429b023d, 0x37d0d724, 3490320968.0, 0.0, 0x49f1c09b, 0x75372c9, 2157517691.0, 0x25d479d8, 0.0, 0.0, 3061402683.0, 0.0, 0x4c006ba, 3249098678.0, 0x409f60c4, 0x5e5c9ec2, 0x196a2463, 0x68fb6faf, 0x3e6c53b5, 0x1339b2eb, 0x3b52ec6f, 0x6dfc511f, 2603652396.0, 3431023940.0, 0.0, 0.0, 0.0, 0x660f2807, 0x192e4bb3, 3234572375.0, 0x45c8740f, 3523960633.0, 3117677531.0, 0x5579c0bd, 0x1a60320a, 3600875718.0, 0x402c7279, 0x679f25fe, 4213154764.0, 0.0, 3677496056.0, 0x3c7516df, 4251020053.0, 0x2f501ec8, 2902807211.0, 0x323db5fa, 4246964064.0, 0x53317b48, 0x3e00df82, 0.0, 3396308128.0, 0x1a87562e, 3742853595.0, 3577915638.0, 0x287effc3, 2892444358.0, 2354009459.0, 0x695b27b0, 3150600392.0, 0.0, 3102740896.0, 0x10fa3d98, 4246832056.0, 0x4afcb56c, 0x2dd1d35b, 0.0, 3069724005.0, 0.0, 0x4bfb9790, 0.0, 0.0, 0x62fb1341, 0.0, 0.0, 0x36774c01, 0.0, 0x2bf11fb4, 2514213453.0, 0.0, 0.0, 0x6b93d5a0, 0.0, 0.0, 0.0, 0.0, 0.0, 4061277028.0, 2290661394.0, 2416832540.0, 0x4fad5ea0, 0x688fc31c, 3520065937.0, 3014181293.0, 0x2f2f2218, 0.0, 0.0, 2332172193.0, 0.0, 0.0, 0x18acf3d6, 0.0, 0.0, 0.0, 0x7cc43b81, 3534596313.0, 0x165fa266, 2157278981.0, 2479649556.0, 0x211a1477, 0.0, 0x77b5fa86, 3344188149.0, 4221384143.0, 0.0, 0x7b3e89a0, 3594591187.0, 0.0, 0x250e2d, 0x2071b35e, 0x226800bb, 0x57b8e0af, 0x2464369b, 0.0, 0x5563911d, 0x59dfa6aa, 0x78c14389, 3646575487.0, 0x207d5ba2, 0x2e5b9c5, 2200306550.0, 0x6295cfa9, 0x11c81968, 0x4e734a41, 3007786442.0, 0x7b14a94a, 0x1b510052, 2589141269.0, 3591329599.0, 0.0, 0x2b60a476, 0.0, 0x8ba6fb5, 0x571be91f, 0.0, 0x2a0dd915, 3059967265.0, 0.0, 0.0, 3313849956.0, 0x53b02d5d, 2845806497.0, 0x8ba4799, 0x6e85076a);
    /**
     * S-Box 1
     *
     * @access private
     * @var    array
     */
    var $sbox1 = array(0x4b7a70e9, 3048417604.0, 0.0, 3289982499.0, 0.0, 0x49a7df7d, 0.0, 0.0, 0.0, 0x699a17ff, 0x5664526c, 0.0, 0x193602a5, 0x75094c29, 2690192192.0, 0.0, 0x3f54989a, 0x5b429d65, 0x6b8fe4d6, 2583117782.0, 2714934279.0, 0.0, 0x4d2d38e6, 4028980673.0, 0x4cdd2086, 0.0, 0x6382e9c6, 0x21ecc5e, 0x9686b3f, 0x3ebaefc9, 0x3c971814, 0x6b6a70a1, 0x687f3584, 0x52a0e286, 3080475397.0, 2857371447.0, 0x3e07841c, 0x7fdeae5c, 0.0, 0x5716f2b8, 2956646967.0, 4031777805.0, 4028374788.0, 0x200b3ff, 0.0, 0x3cb574b2, 0x25837a58, 3691585981.0, 3515945977.0, 0x7ca92ff6, 2486323059.0, 0x22f54701, 0x3ae5e581, 0x37c2dadc, 3367335476.0, 2599673255.0, 2839830854.0, 0xfd0030e, 0.0, 0.0, 0.0, 0x3bea0e2f, 0x3280bba1, 0x183eb331, 0x4e548b38, 0x4f6db908, 0x6f420d03, 4127851711.0, 0x2cb81290, 0x24977c79, 0x5679b072, 3165620655.0, 0.0, 3650291728.0, 0.0, 0.0, 0x5512721f, 0x2e6b7124, 0x501adde6, 2676280711.0, 0x7a584718, 0x7408da17, 3164576444.0, 0.0, 0.0, 3682934266.0, 0x63094366, 3294938066.0, 0.0, 0x3215d908, 3712170807.0, 0x24c2ba16, 0x12a14d43, 0x2a65c451, 0x50940002, 0x133ae4dd, 0x71dff89e, 0x10314e55, 2175563734.0, 0x5f11199b, 0x43556f1, 3617834859.0, 0x3c11183b, 0x5924a509, 0.0, 2549218298.0, 0.0, 0x1e153c6e, 0.0, 0.0, 0.0, 0x5a3e2ab3, 0x771fe71c, 0x4e3d06fa, 0x2965dcb9, 0.0, 0.0, 0x5266c825, 0x2e4cc978, 2618340202.0, 0.0, 0.0, 2784771155.0, 0x1e0a2df4, 0.0, 0x361d2b3d, 0x1939260f, 0x19c27960, 0x5223a708, 4145222326.0, 0.0, 0.0, 0.0, 2793130115.0, 2977904593.0, 0x18cff28, 0.0, 0.0, 0x65582185, 0x68ab9802, 0.0, 3677328699.0, 0x2aef7dad, 0x5b6e2f84, 0x1521b628, 0x29076170, 0.0, 0x619f1510, 0x13cca830, 0.0, 0x334fe1e, 2852348879.0, 3044236432.0, 0x4c70a239, 0.0, 0.0, 0.0, 0x60622ca7, 2628476075.0, 0.0, 0x648b1eaf, 0x19bdf0ca, 2686675385.0, 0x655abb50, 0x40685a32, 0x3c2ab4b3, 0x319ee9d5, 3223435511.0, 2605976345.0, 2271191193.0, 0.0, 0x623d7da8, 4164389018.0, 0.0, 0x11ed935f, 0x16681281, 0xe358829, 0.0, 0.0, 0x7858ba99, 0x57f584a5, 0x1b227263, 2609103871.0, 0x1ac24696, 0.0, 0x532e3054, 0.0, 0x6dbc3128, 0x58ebf2ef, 0x34c6ffea, 0.0, 0.0, 0x5d4a14d9, 0.0, 0x42105d14, 0x203e13e0, 0x45eee2b6, 0.0, 3681308437.0, 4207628240.0, 3343053890.0, 0.0, 0x654f3b1d, 0x41cd2105, 0.0, 2256883143.0, 0.0, 0x3d816250, 3479347698.0, 0x5b8d2646, 4236805024.0, 3251091107.0, 0x7f1524c3, 0x69cb7492, 0x47848a0b, 0x5692b285, 0x95bbf00, 2904115357.0, 0x1462b174, 0x23820e00, 0x58428d2a, 0xc55f5ea, 0x1dadf43e, 0x233f7061, 0x3372f092, 0.0, 0.0, 0x6c223bdb, 0x7cde3759, 0.0, 0x4085f2a7, 0.0, 2785509508.0, 0x19f8509e, 0.0, 0x61d99735, 2842273706.0, 3305899714.0, 0x5a04abfc, 2148256476.0, 0.0, 3276092548.0, 4258621189.0, 0xe1e9ec9, 3681803219.0, 0x105588cd, 0x675fda79, 0.0, 3317970021.0, 0x713e38d8, 0x3d28f89e, 4050517792.0, 0x153e21e7, 2410691914.0, 0.0, 3682840055.0);
    /**
     * S-Box 2
     *
     * @access private
     * @var    array
     */
    var $sbox2 = array(0.0, 2491498743.0, 4132185628.0, 2489919796.0, 0x411520f7, 0x7602d4f7, 0.0, 3567386728.0, 3557303409.0, 0x3320f46a, 0x43b7d4b7, 0x500061af, 0x1e39f62e, 2535736646.0, 0x14214f74, 3213592640.0, 0x4d95fc1d, 2528481711.0, 0x70f4ddd3, 0x66a02f45, 0.0, 0x3bd9785, 0x7fac6dd0, 0x31cb8504, 0.0, 0x55fd3941, 0.0, 2882144922.0, 0x28507825, 0x530429f4, 0xa2c86da, 0.0, 0x68dc1462, 3611846912.0, 0x680ec0a4, 0x27a18dee, 0x4f3ffea2, 0.0, 0.0, 0x7af4d6b6, 0.0, 0.0, 0.0, 0x406b2a42, 0x20fe9e35, 3656615353.0, 0.0, 0x3b124e8b, 0x1dc9faf7, 0x4b6d1856, 0x26a36631, 0.0, 0x3a6efa74, 3713745714.0, 0x6841e7f7, 3396870395.0, 0.0, 0.0, 0x454056ac, 3125318951.0, 0x55533a3a, 0x20838d87, 0.0, 3499529547.0, 0x55a867bc, 2702547544.0, 3433638243.0, 0.0, 2787789398.0, 0x3f3125f9, 0x5ef47e1c, 2418618748.0, 0.0, 0x4272f70, 2159744348.0, 0x5282ce3, 2512459080.0, 0.0, 0x48c1133f, 3339683548.0, 0x7f9c9ee, 0x41041f0f, 0x404779a4, 0x5d886e17, 0x325f51eb, 3583754449.0, 4072456591.0, 0x41113564, 0x257b7834, 0x602a9c60, 0.0, 0x1f636c1b, 0xe12b4c2, 0x2e1329e, 2942717905.0, 3402727701.0, 0x6b2395e0, 0x333e92e1, 0x3b240b62, 0.0, 0.0, 0.0, 0.0, 0x2da2f728, 3490871365.0, 2511836413.0, 0x647d0862, 0.0, 0x5449a36f, 2273134842.0, 3281911079.0, 0.0, 0xa476341, 0.0, 0x3a6f6eab, 4109958455.0, 2819808352.0, 0.0, 0.0, 0.0, 3329971472.0, 0x6d672c37, 0x2765d43b, 0.0, 4045999559.0, 3422617507.0, 3040415634.0, 0x690fed0b, 0x667b9ffb, 0.0, 2693910283.0, 0.0, 3138596744.0, 0x515bad24, 0x7b9479bf, 0x763bd6eb, 0x37392eb3, 3423689081.0, 0.0, 0.0, 0x6842ada7, 3328846651.0, 0x12754ccc, 0x782ef11c, 0x6a124237, 0.0, 0x6a1bbe6, 0x4bfb6350, 0x1a6b1018, 0x11caedfa, 0x3d25bdd8, 0.0, 0x44421659, 0xa121386, 0.0, 0.0, 0x64af674e, 3666258015.0, 0.0, 0x64e4c3fe, 2646376535.0, 4042768518.0, 0x60787bf8, 0x6003604d, 3523052358.0, 4130873264.0, 0x7745ae04, 3610705100.0, 2202168115.0, 0.0, 2961195399.0, 0x3c005e5f, 0x77a057be, 0.0, 0x55464299, 0.0, 0x4e58f48f, 4074634658.0, 0.0, 2273951170.0, 0x5366f9c3, 0.0, 3027628629.0, 0x46fcd9b9, 0x7aeb2661, 2333990788.0, 0.0, 0.0, 0x466e598e, 0x20b45770, 2362791313.0, 0.0, 0.0, 3145860560.0, 0x11a86248, 0x7574a99e, 3078560182.0, 0.0, 0x662d09a1, 3291629107.0, 0.0, 0x9f0be8c, 0x4a99a025, 0x1d6efe10, 0x1ab93d1d, 0xba5a4df, 2709975567.0, 0x2868f169, 3703036547.0, 0x573906fe, 0.0, 0x4fcd7f52, 0x50115e01, 2802222074.0, 2684532164.0, 0xde6d027, 2599980071.0, 0x773f8641, 3277868038.0, 0x61a806b5, 4028070440.0, 0.0, 0x6058aa, 0x30dc7d62, 0x11e69ed7, 0x2338ea63, 0x53c2dd94, 3267499572.0, 0.0, 0.0, 0.0, 0.0, 0x6f05e409, 0x4b7c0188, 0x39720a3d, 0x7c927c24, 0.0, 0x724d9db9, 0x1ac15bb4, 0.0, 0.0, 0x8fca5b5, 3627908307.0, 0x4dad0fc4, 0x1e50ef5e, 0.0, 2726630617.0, 0x6c51133c, 0x6fd5c7e7, 0x56e14ec4, 0x362abfce, 3720792119.0, 3617206836.0, 2455994898.0, 0x670efa8e, 0x406000e0);
    /**
     * S-Box 3
     *
     * @access private
     * @var    array
     */
    var $sbox3 = array(0x3a39ce37, 3556439503.0, 2881648439.0, 0x5ac52d1b, 0x5cb0679e, 0x4fa33742, 3548522304.0, 0.0, 0.0, 3205460757.0, 0.0, 3338716283.0, 3079412587.0, 0x21a19045, 0.0, 0x6a366eb4, 0x5748ab2f, 0.0, 3332601554.0, 0x6549c2c8, 0x530ff8ee, 0x468dde7d, 3581086237.0, 0x4cd04dc6, 0x2939bbdb, 2847557200.0, 0.0, 0.0, 2717570544.0, 0x6a2d519a, 0x63ef8ce2, 0.0, 3230253752.0, 0x43242ef6, 0.0, 2633158820.0, 2210423226.0, 0.0, 0.0, 3127139286.0, 0x2826a2f9, 0.0, 0x4ba99586, 0.0, 0.0, 4149409754.0, 0x3f046f69, 0x77fa0a59, 0.0, 2276492801.0, 0.0, 0x3b3ee593, 0.0, 0.0, 0x2cf0b7d9, 0x22b8b51, 2530585658.0, 0x17da67d, 0.0, 0x7c7d2d28, 0x1f9f25cf, 2918365339.0, 0x5ad6b472, 0x5a88f54c, 0.0, 0.0, 0x47b0acfd, 0.0, 0.0, 0x283b57cc, 4174734889.0, 0x79132e28, 0x785f0191, 0.0, 0.0, 0.0, 0x15056dd4, 2297720250.0, 0x3a16125, 0x564f0bd, 0.0, 0x3c9057a2, 0.0, 2839152426.0, 0x1b3f6d9b, 0x1e6321f5, 4120667899.0, 0x26dcf319, 0x7533d928, 2975202805.0, 0x3563482, 2327461051.0, 0x28517711, 3255491064.0, 2882294119.0, 3433927263.0, 0x4de81751, 0x3830dc8e, 0x379d5862, 2468411793.0, 0.0, 0.0, 0x5121ce64, 0x774fbe32, 0.0, 3274259782.0, 0x48de5369, 0x6413e680, 0.0, 3714953764.0, 0x69852dfd, 0x9072166, 3013232138.0, 0x6445c0dd, 0x586cdecf, 0x1c20c8ae, 0x5bbef7dd, 0x1b588d40, 3436315007.0, 0x6bb4e3bb, 0.0, 0x3a59ff45, 0x3e350a44, 3165965781.0, 0x72eacea8, 4200891579.0, 0.0, 3208408903.0, 0.0, 0x542f5d9e, 0.0, 0.0, 0x740e0d8d, 0.0, 4168226417.0, 2941484381.0, 0x4040cb08, 0x4eb4e2cc, 0x34d2466a, 0x115af84, 0.0, 2509781533.0, 0x6b89fb4, 0.0, 0x6f3f3b82, 0x3520ab82, 0x11a1d4b, 0x277227f8, 0x611560b1, 0.0, 3141171499.0, 0x344525bd, 0.0, 0x51ce794b, 0x2f32c9b7, 2686433993.0, 0.0, 3167212022.0, 3472953795.0, 0.0, 0x1a908749, 3561995674.0, 0.0, 3574258232.0, 0x339c32a, 3331405415.0, 2381918588.0, 0.0, 0.0, 0x43f5bb3a, 4074052095.0, 0x27d9459c, 3214352940.0, 0x15e6fc2a, 0xf91fc71, 2610173221.0, 0.0, 0.0, 3265815641.0, 0x12baa8d1, 0.0, 0.0, 0x10d25065, 3406013506.0, 0.0, 0x1698db3b, 0x4c98a0be, 0x3278e964, 2669647154.0, 0.0, 3550491691.0, 0.0, 0x1b0a7441, 0x4ba3348c, 0.0, 3279303384.0, 3744833421.0, 0.0, 0.0, 0xfe3f11d, 0.0, 0x1edad891, 0.0, 0.0, 0x1618b166, 4247526661.0, 2224018117.0, 4143653529.0, 4112773975.0, 2788324899.0, 2477274417.0, 0x56cccd02, 2901442914.0, 0x5a75ebb5, 0x6e163697, 2295493580.0, 0.0, 2176403920.0, 0x4c50901b, 0x71c65614, 0.0, 0x327a140a, 0x45e1d006, 3287448474.0, 3383383037.0, 0x62a80f00, 0.0, 0x35bdd2f6, 0x71126905, 2986607138.0, 3066810236.0, 3447102507.0, 0x53113ec0, 0x1640e3d3, 0x38abbd60, 0x2547adf0, 3124240540.0, 0.0, 0x77afa1c5, 0x20756060, 0.0, 0.0, 0x7aaaf9b0, 0x4cf9aa7e, 0x1948c25c, 0x2fb8a8c, 0x1c36ae4, 0.0, 2429876329.0, 0.0, 0x3f09252d, 0.0, 0.0, 0.0, 0x578fdfe3, 0x3ac372e6);
    /**
     * P-Array consists of 18 32-bit subkeys
     *
     * @var array
     * @access private
     */
    var $parray = array(0x243f6a88, 2242054355.0, 0x13198a2e, 0x3707344, 2752067618.0, 0x299f31d0, 0x82efa98, 0.0, 0x452821e6, 0x38d01377, 0.0, 0x34e90c6c, 3232508343.0, 3380367581.0, 0x3f84d5b5, 3041331479.0, 2450970073.0, 2306472731.0);
    /**
     * The BCTX-working Array
     *
     * Holds the expanded key [p] and the key-depended s-boxes [sb]
     *
     * @var array
     * @access private
     */
    var $bctx;
    /**
     * Holds the last used key
     *
     * @var array
     * @access private
     */
    var $kl;
    /**
     * The Key Length (in bytes)
     *
     * @see \phpseclib\Crypt\Base::setKeyLength()
     * @var int
     * @access private
     * @internal The max value is 256 / 8 = 32, the min value is 128 / 8 = 16.  Exists in conjunction with $Nk
     *    because the encryption / decryption / key schedule creation requires this number and not $key_length.  We could
     *    derive this from $key_length or vice versa, but that'd mean we'd have to do multiple shift operations, so in lieu
     *    of that, we'll just precompute it once.
     */
    var $key_length = 16;
    /**
     * Sets the key length.
     *
     * Key lengths can be between 32 and 448 bits.
     *
     * @access public
     * @param int $length
     */
    function setKeyLength($length)
    {
        if ($length < 32) {
            $this->key_length = 4;
        } elseif ($length > 448) {
            $this->key_length = 56;
        } else {
            $this->key_length = $length >> 3;
        }
        parent::setKeyLength($length);
    }
    /**
     * Test for engine validity
     *
     * This is mainly just a wrapper to set things up for \phpseclib\Crypt\Base::isValidEngine()
     *
     * @see \phpseclib\Crypt\Base::isValidEngine()
     * @param int $engine
     * @access public
     * @return bool
     */
    function isValidEngine($engine)
    {
        if ($engine == self::ENGINE_OPENSSL) {
            if (version_compare(PHP_VERSION, '5.3.7') < 0 && $this->key_length != 16) {
                return false;
            }
            if ($this->key_length < 16) {
                return false;
            }
            $this->cipher_name_openssl_ecb = 'bf-ecb';
            $this->cipher_name_openssl = 'bf-' . $this->_openssl_translate_mode();
        }
        return parent::isValidEngine($engine);
    }
    /**
     * Setup the key (expansion)
     *
     * @see \phpseclib\Crypt\Base::_setupKey()
     * @access private
     */
    function _setupKey()
    {
        if (isset($this->kl['key']) && $this->key === $this->kl['key']) {
            // already expanded
            return;
        }
        $this->kl = array('key' => $this->key);
        /* key-expanding p[] and S-Box building sb[] */
        $this->bctx = array('p' => array(), 'sb' => array($this->sbox0, $this->sbox1, $this->sbox2, $this->sbox3));
        // unpack binary string in unsigned chars
        $key = array_values(unpack('C*', $this->key));
        $keyl = count($key);
        for ($j = 0, $i = 0; $i < 18; ++$i) {
            // xor P1 with the first 32-bits of the key, xor P2 with the second 32-bits ...
            for ($data = 0, $k = 0; $k < 4; ++$k) {
                $data = $data << 8 | $key[$j];
                if (++$j >= $keyl) {
                    $j = 0;
                }
            }
            $this->bctx['p'][] = $this->parray[$i] ^ $data;
        }
        // encrypt the zero-string, replace P1 and P2 with the encrypted data,
        // encrypt P3 and P4 with the new P1 and P2, do it with all P-array and subkeys
        $data = "\0\0\0\0\0\0\0\0";
        for ($i = 0; $i < 18; $i += 2) {
            list($l, $r) = array_values(unpack('N*', $data = $this->_encryptBlock($data)));
            $this->bctx['p'][$i] = $l;
            $this->bctx['p'][$i + 1] = $r;
        }
        for ($i = 0; $i < 4; ++$i) {
            for ($j = 0; $j < 256; $j += 2) {
                list($l, $r) = array_values(unpack('N*', $data = $this->_encryptBlock($data)));
                $this->bctx['sb'][$i][$j] = $l;
                $this->bctx['sb'][$i][$j + 1] = $r;
            }
        }
    }
    /**
     * Encrypts a block
     *
     * @access private
     * @param string $in
     * @return string
     */
    function _encryptBlock($in)
    {
        $p = $this->bctx["p"];
        // extract($this->bctx["sb"], EXTR_PREFIX_ALL, "sb"); // slower
        $sb_0 = $this->bctx["sb"][0];
        $sb_1 = $this->bctx["sb"][1];
        $sb_2 = $this->bctx["sb"][2];
        $sb_3 = $this->bctx["sb"][3];
        $in = unpack("N*", $in);
        $l = $in[1];
        $r = $in[2];
        for ($i = 0; $i < 16; $i += 2) {
            $l ^= $p[$i];
            $r ^= $this->safe_intval(($this->safe_intval($sb_0[$l >> 24 & 0xff] + $sb_1[$l >> 16 & 0xff]) ^ $sb_2[$l >> 8 & 0xff]) + $sb_3[$l & 0xff]);
            $r ^= $p[$i + 1];
            $l ^= $this->safe_intval(($this->safe_intval($sb_0[$r >> 24 & 0xff] + $sb_1[$r >> 16 & 0xff]) ^ $sb_2[$r >> 8 & 0xff]) + $sb_3[$r & 0xff]);
        }
        return pack("N*", $r ^ $p[17], $l ^ $p[16]);
    }
    /**
     * Decrypts a block
     *
     * @access private
     * @param string $in
     * @return string
     */
    function _decryptBlock($in)
    {
        $p = $this->bctx["p"];
        $sb_0 = $this->bctx["sb"][0];
        $sb_1 = $this->bctx["sb"][1];
        $sb_2 = $this->bctx["sb"][2];
        $sb_3 = $this->bctx["sb"][3];
        $in = unpack("N*", $in);
        $l = $in[1];
        $r = $in[2];
        for ($i = 17; $i > 2; $i -= 2) {
            $l ^= $p[$i];
            $r ^= $this->safe_intval(($this->safe_intval($sb_0[$l >> 24 & 0xff] + $sb_1[$l >> 16 & 0xff]) ^ $sb_2[$l >> 8 & 0xff]) + $sb_3[$l & 0xff]);
            $r ^= $p[$i - 1];
            $l ^= $this->safe_intval(($this->safe_intval($sb_0[$r >> 24 & 0xff] + $sb_1[$r >> 16 & 0xff]) ^ $sb_2[$r >> 8 & 0xff]) + $sb_3[$r & 0xff]);
        }
        return pack("N*", $r ^ $p[0], $l ^ $p[1]);
    }
    /**
     * Setup the performance-optimized function for de/encrypt()
     *
     * @see \phpseclib\Crypt\Base::_setupInlineCrypt()
     * @access private
     */
    function _setupInlineCrypt()
    {
        $lambda_functions =& self::_getLambdaFunctions();
        // We create max. 10 hi-optimized code for memory reason. Means: For each $key one ultra fast inline-crypt function.
        // (Currently, for Blowfish, one generated $lambda_function cost on php5.5@32bit ~100kb unfreeable mem and ~180kb on php5.5@64bit)
        // After that, we'll still create very fast optimized code but not the hi-ultimative code, for each $mode one.
        $gen_hi_opt_code = (bool) (count($lambda_functions) < 10);
        // Generation of a unique hash for our generated code
        $code_hash = "Crypt_Blowfish, {$this->mode}";
        if ($gen_hi_opt_code) {
            $code_hash = str_pad($code_hash, 32) . $this->_hashInlineCryptFunction($this->key);
        }
        $safeint = $this->safe_intval_inline();
        if (!isset($lambda_functions[$code_hash])) {
            switch (true) {
                case $gen_hi_opt_code:
                    $p = $this->bctx['p'];
                    $init_crypt = '
                        static $sb_0, $sb_1, $sb_2, $sb_3;
                        if (!$sb_0) {
                            $sb_0 = $self->bctx["sb"][0];
                            $sb_1 = $self->bctx["sb"][1];
                            $sb_2 = $self->bctx["sb"][2];
                            $sb_3 = $self->bctx["sb"][3];
                        }
                    ';
                    break;
                default:
                    $p = array();
                    for ($i = 0; $i < 18; ++$i) {
                        $p[] = '$p_' . $i;
                    }
                    $init_crypt = '
                        list($sb_0, $sb_1, $sb_2, $sb_3) = $self->bctx["sb"];
                        list(' . implode(',', $p) . ') = $self->bctx["p"];

                    ';
            }
            // Generating encrypt code:
            $encrypt_block = '
                $in = unpack("N*", $in);
                $l = $in[1];
                $r = $in[2];
            ';
            for ($i = 0; $i < 16; $i += 2) {
                $encrypt_block .= '
                    $l^= ' . $p[$i] . ';
                    $r^= ' . sprintf($safeint, '(' . sprintf($safeint, '$sb_0[$l >> 24 & 0xff] + $sb_1[$l >> 16 & 0xff]') . ' ^
                          $sb_2[$l >>  8 & 0xff]) +
                          $sb_3[$l       & 0xff]') . ';

                    $r^= ' . $p[$i + 1] . ';
                    $l^= ' . sprintf($safeint, '(' . sprintf($safeint, '$sb_0[$r >> 24 & 0xff] + $sb_1[$r >> 16 & 0xff]') . '  ^
                          $sb_2[$r >>  8 & 0xff]) +
                          $sb_3[$r       & 0xff]') . ';
                ';
            }
            $encrypt_block .= '
                $in = pack("N*",
                    $r ^ ' . $p[17] . ',
                    $l ^ ' . $p[16] . '
                );
            ';
            // Generating decrypt code:
            $decrypt_block = '
                $in = unpack("N*", $in);
                $l = $in[1];
                $r = $in[2];
            ';
            for ($i = 17; $i > 2; $i -= 2) {
                $decrypt_block .= '
                    $l^= ' . $p[$i] . ';
                    $r^= ' . sprintf($safeint, '(' . sprintf($safeint, '$sb_0[$l >> 24 & 0xff] + $sb_1[$l >> 16 & 0xff]') . ' ^
                          $sb_2[$l >>  8 & 0xff]) +
                          $sb_3[$l       & 0xff]') . ';

                    $r^= ' . $p[$i - 1] . ';
                    $l^= ' . sprintf($safeint, '(' . sprintf($safeint, '$sb_0[$r >> 24 & 0xff] + $sb_1[$r >> 16 & 0xff]') . ' ^
                          $sb_2[$r >>  8 & 0xff]) +
                          $sb_3[$r       & 0xff]') . ';
                ';
            }
            $decrypt_block .= '
                $in = pack("N*",
                    $r ^ ' . $p[0] . ',
                    $l ^ ' . $p[1] . '
                );
            ';
            $lambda_functions[$code_hash] = $this->_createInlineCryptFunction(array('init_crypt' => $init_crypt, 'init_encrypt' => '', 'init_decrypt' => '', 'encrypt_block' => $encrypt_block, 'decrypt_block' => $decrypt_block));
        }
        $this->inline_crypt = $lambda_functions[$code_hash];
    }
}

?>