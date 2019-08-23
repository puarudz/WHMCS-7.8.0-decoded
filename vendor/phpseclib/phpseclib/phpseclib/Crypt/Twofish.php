<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

/**
 * Pure-PHP implementation of Twofish.
 *
 * Uses mcrypt, if available, and an internal implementation, otherwise.
 *
 * PHP version 5
 *
 * Useful resources are as follows:
 *
 *  - {@link http://en.wikipedia.org/wiki/Twofish Wikipedia description of Twofish}
 *
 * Here's a short example of how to use this library:
 * <code>
 * <?php
 *    include 'vendor/autoload.php';
 *
 *    $twofish = new \phpseclib\Crypt\Twofish();
 *
 *    $twofish->setKey('12345678901234567890123456789012');
 *
 *    $plaintext = str_repeat('a', 1024);
 *
 *    echo $twofish->decrypt($twofish->encrypt($plaintext));
 * ?>
 * </code>
 *
 * @category  Crypt
 * @package   Twofish
 * @author    Jim Wigginton <terrafrost@php.net>
 * @author    Hans-Juergen Petrich <petrich@tronic-media.com>
 * @copyright 2007 Jim Wigginton
 * @license   http://www.opensource.org/licenses/mit-license.html  MIT License
 * @link      http://phpseclib.sourceforge.net
 */
namespace phpseclib\Crypt;

/**
 * Pure-PHP implementation of Twofish.
 *
 * @package Twofish
 * @author  Jim Wigginton <terrafrost@php.net>
 * @author  Hans-Juergen Petrich <petrich@tronic-media.com>
 * @access  public
 */
class Twofish extends Base
{
    /**
     * The mcrypt specific name of the cipher
     *
     * @see \phpseclib\Crypt\Base::cipher_name_mcrypt
     * @var string
     * @access private
     */
    var $cipher_name_mcrypt = 'twofish';
    /**
     * Optimizing value while CFB-encrypting
     *
     * @see \phpseclib\Crypt\Base::cfb_init_len
     * @var int
     * @access private
     */
    var $cfb_init_len = 800;
    /**
     * Q-Table
     *
     * @var array
     * @access private
     */
    var $q0 = array(0xa9, 0x67, 0xb3, 0xe8, 0x4, 0xfd, 0xa3, 0x76, 0x9a, 0x92, 0x80, 0x78, 0xe4, 0xdd, 0xd1, 0x38, 0xd, 0xc6, 0x35, 0x98, 0x18, 0xf7, 0xec, 0x6c, 0x43, 0x75, 0x37, 0x26, 0xfa, 0x13, 0x94, 0x48, 0xf2, 0xd0, 0x8b, 0x30, 0x84, 0x54, 0xdf, 0x23, 0x19, 0x5b, 0x3d, 0x59, 0xf3, 0xae, 0xa2, 0x82, 0x63, 0x1, 0x83, 0x2e, 0xd9, 0x51, 0x9b, 0x7c, 0xa6, 0xeb, 0xa5, 0xbe, 0x16, 0xc, 0xe3, 0x61, 0xc0, 0x8c, 0x3a, 0xf5, 0x73, 0x2c, 0x25, 0xb, 0xbb, 0x4e, 0x89, 0x6b, 0x53, 0x6a, 0xb4, 0xf1, 0xe1, 0xe6, 0xbd, 0x45, 0xe2, 0xf4, 0xb6, 0x66, 0xcc, 0x95, 0x3, 0x56, 0xd4, 0x1c, 0x1e, 0xd7, 0xfb, 0xc3, 0x8e, 0xb5, 0xe9, 0xcf, 0xbf, 0xba, 0xea, 0x77, 0x39, 0xaf, 0x33, 0xc9, 0x62, 0x71, 0x81, 0x79, 0x9, 0xad, 0x24, 0xcd, 0xf9, 0xd8, 0xe5, 0xc5, 0xb9, 0x4d, 0x44, 0x8, 0x86, 0xe7, 0xa1, 0x1d, 0xaa, 0xed, 0x6, 0x70, 0xb2, 0xd2, 0x41, 0x7b, 0xa0, 0x11, 0x31, 0xc2, 0x27, 0x90, 0x20, 0xf6, 0x60, 0xff, 0x96, 0x5c, 0xb1, 0xab, 0x9e, 0x9c, 0x52, 0x1b, 0x5f, 0x93, 0xa, 0xef, 0x91, 0x85, 0x49, 0xee, 0x2d, 0x4f, 0x8f, 0x3b, 0x47, 0x87, 0x6d, 0x46, 0xd6, 0x3e, 0x69, 0x64, 0x2a, 0xce, 0xcb, 0x2f, 0xfc, 0x97, 0x5, 0x7a, 0xac, 0x7f, 0xd5, 0x1a, 0x4b, 0xe, 0xa7, 0x5a, 0x28, 0x14, 0x3f, 0x29, 0x88, 0x3c, 0x4c, 0x2, 0xb8, 0xda, 0xb0, 0x17, 0x55, 0x1f, 0x8a, 0x7d, 0x57, 0xc7, 0x8d, 0x74, 0xb7, 0xc4, 0x9f, 0x72, 0x7e, 0x15, 0x22, 0x12, 0x58, 0x7, 0x99, 0x34, 0x6e, 0x50, 0xde, 0x68, 0x65, 0xbc, 0xdb, 0xf8, 0xc8, 0xa8, 0x2b, 0x40, 0xdc, 0xfe, 0x32, 0xa4, 0xca, 0x10, 0x21, 0xf0, 0xd3, 0x5d, 0xf, 0x0, 0x6f, 0x9d, 0x36, 0x42, 0x4a, 0x5e, 0xc1, 0xe0);
    /**
     * Q-Table
     *
     * @var array
     * @access private
     */
    var $q1 = array(0x75, 0xf3, 0xc6, 0xf4, 0xdb, 0x7b, 0xfb, 0xc8, 0x4a, 0xd3, 0xe6, 0x6b, 0x45, 0x7d, 0xe8, 0x4b, 0xd6, 0x32, 0xd8, 0xfd, 0x37, 0x71, 0xf1, 0xe1, 0x30, 0xf, 0xf8, 0x1b, 0x87, 0xfa, 0x6, 0x3f, 0x5e, 0xba, 0xae, 0x5b, 0x8a, 0x0, 0xbc, 0x9d, 0x6d, 0xc1, 0xb1, 0xe, 0x80, 0x5d, 0xd2, 0xd5, 0xa0, 0x84, 0x7, 0x14, 0xb5, 0x90, 0x2c, 0xa3, 0xb2, 0x73, 0x4c, 0x54, 0x92, 0x74, 0x36, 0x51, 0x38, 0xb0, 0xbd, 0x5a, 0xfc, 0x60, 0x62, 0x96, 0x6c, 0x42, 0xf7, 0x10, 0x7c, 0x28, 0x27, 0x8c, 0x13, 0x95, 0x9c, 0xc7, 0x24, 0x46, 0x3b, 0x70, 0xca, 0xe3, 0x85, 0xcb, 0x11, 0xd0, 0x93, 0xb8, 0xa6, 0x83, 0x20, 0xff, 0x9f, 0x77, 0xc3, 0xcc, 0x3, 0x6f, 0x8, 0xbf, 0x40, 0xe7, 0x2b, 0xe2, 0x79, 0xc, 0xaa, 0x82, 0x41, 0x3a, 0xea, 0xb9, 0xe4, 0x9a, 0xa4, 0x97, 0x7e, 0xda, 0x7a, 0x17, 0x66, 0x94, 0xa1, 0x1d, 0x3d, 0xf0, 0xde, 0xb3, 0xb, 0x72, 0xa7, 0x1c, 0xef, 0xd1, 0x53, 0x3e, 0x8f, 0x33, 0x26, 0x5f, 0xec, 0x76, 0x2a, 0x49, 0x81, 0x88, 0xee, 0x21, 0xc4, 0x1a, 0xeb, 0xd9, 0xc5, 0x39, 0x99, 0xcd, 0xad, 0x31, 0x8b, 0x1, 0x18, 0x23, 0xdd, 0x1f, 0x4e, 0x2d, 0xf9, 0x48, 0x4f, 0xf2, 0x65, 0x8e, 0x78, 0x5c, 0x58, 0x19, 0x8d, 0xe5, 0x98, 0x57, 0x67, 0x7f, 0x5, 0x64, 0xaf, 0x63, 0xb6, 0xfe, 0xf5, 0xb7, 0x3c, 0xa5, 0xce, 0xe9, 0x68, 0x44, 0xe0, 0x4d, 0x43, 0x69, 0x29, 0x2e, 0xac, 0x15, 0x59, 0xa8, 0xa, 0x9e, 0x6e, 0x47, 0xdf, 0x34, 0x35, 0x6a, 0xcf, 0xdc, 0x22, 0xc9, 0xc0, 0x9b, 0x89, 0xd4, 0xed, 0xab, 0x12, 0xa2, 0xd, 0x52, 0xbb, 0x2, 0x2f, 0xa9, 0xd7, 0x61, 0x1e, 0xb4, 0x50, 0x4, 0xf6, 0xc2, 0x16, 0x25, 0x86, 0x56, 0x55, 0x9, 0xbe, 0x91);
    /**
     * M-Table
     *
     * @var array
     * @access private
     */
    var $m0 = array(3166450293.0, 0.0, 0x202043c6, 3014904308.0, 3671720923.0, 0x2028b7b, 0.0, 0.0, 0.0, 3570665939.0, 0x18186be6, 0x1e1e9f6b, 0.0, 2998024317.0, 0.0, 0x2626b74b, 0x3c3c57d6, 2475919922.0, 0.0, 0x525298fd, 0x7b7bd437, 3149608817.0, 0x5b5b97f1, 0x474783e1, 0x24243c30, 0x5151e20f, 3132802808.0, 0x4a4af31b, 3216984199.0, 0xd0d70fa, 2964370182.0, 0x7575de3f, 0.0, 0x7d7d20ba, 0x666631ae, 0x3a3aa35b, 0x59591c8a, 0x0, 3452801980.0, 0x1a1ae09d, 0.0, 0x7f7fabc1, 0x2b2bc7b1, 0.0, 0.0, 2324303965.0, 0x3b3b52d2, 0x6464bad5, 3638069408.0, 0.0, 0x5f5fe807, 0x1b1b1114, 0x2c2cc2b5, 4244419728.0, 0x3131272c, 2155898275.0, 0x73732ab2, 0xc0c8173, 0x79795f4c, 0x6b6b4154, 0x4b4b0292, 0x53536974, 2492763958.0, 2206408529.0, 0x2a2a3638, 3301219504.0, 0x2222c8bd, 3587569754.0, 3183330300.0, 0x48487860, 0.0, 0x4c4c0796, 0x4141776c, 0.0, 0.0, 0x1c1c1410, 0x5d5d637c, 0x36362228, 0x6767c027, 0.0, 0x4444f913, 0x1414ea95, 4126522268.0, 3486456007.0, 0x3f3f2d24, 0.0, 0x7272db3b, 0x54546c70, 0x29294cca, 0.0, 0x808fe85, 3334870987.0, 4092808977.0, 0.0, 2762234259.0, 3402274488.0, 0x68683ba6, 3099086211.0, 0x38382820, 0.0, 2913818271.0, 0xb0b8477, 3368558019.0, 2577006540.0, 0x5858ed03, 0x19199a6f, 0xe0e0a08, 0.0, 0x70705040, 0.0, 0x6e6ecf2b, 0x1f1f6ee2, 3048553849.0, 0x9090f0c, 0x616134aa, 0x57571682, 2678000449.0, 2644344890.0, 0x111164ea, 0x2525cdb9, 0.0, 0x4545089a, 3755969956.0, 2745392279.0, 0.0, 0x353558da, 0.0, 0x4343fc17, 4177054566.0, 4227576212.0, 0x3737d3a1, 4210704413.0, 3267520573.0, 3031747824.0, 0x32325dde, 2627498419.0, 0x5656e70b, 0.0, 2273796263.0, 0x15151b1c, 0.0, 0x6363bfd1, 0x3434a953, 0.0, 2981184143.0, 0x7c7cd133, 2290653990.0, 0x3d3da65f, 0.0, 0.0, 2172752938.0, 2442199369.0, 0xf0ffb81, 0.0, 0x161661ee, 3621221153.0, 2543318468.0, 2779097114.0, 0.0, 0x6d6db5d9, 0x7878aec5, 3318050105.0, 0x1d1de599, 0x7676a4cd, 0x3e3edcad, 3419105073.0, 3065399179.0, 0.0, 0x12121e18, 0x6060c523, 0x6a6ab0dd, 0x4d4df61f, 0.0, 0.0, 0x55559df9, 0x7e7e5a48, 0x2121b24f, 0x3037af2, 2694850149.0, 0x5e5e198e, 0x5a5a6678, 0x65654b5c, 0x62624e58, 4261233945.0, 0x606f48d, 0x404086e5, 0.0, 0x3333ac57, 0x17179067, 0x5058e7f, 0.0, 0x4f4f7d64, 2307484335.0, 0x10109563, 0x74742fb6, 0xa0a75fe, 0x5c5c92f5, 2610656439.0, 0x2d2d333c, 0x3030d6a5, 0x2e2e49ce, 0x494989e9, 0x46467268, 0x77775544, 0.0, 2526413901.0, 0x2828bd43, 2846435689.0, 3654908201.0, 0.0, 3520169900.0, 4109650453.0, 2374833497.0, 3604382376.0, 3115957258.0, 0x42420d9e, 0.0, 0x2f2fb847, 3722249951.0, 0x23233934, 3435946549.0, 4059153514.0, 3250655951.0, 0.0, 0.0, 0x7171a1c9, 2425417920.0, 2863289243.0, 0x101f189, 0.0, 0x4e4e8ced, 0.0, 2880152082.0, 0x6f6f3ea2, 0.0, 3688624722.0, 2459073467.0, 3082270210.0, 0x6969ca2f, 0x3939d9a9, 3553823959.0, 2812748641.0, 0.0, 3284375988.0, 0x6c6c4450, 0x7070504, 0x4047ff6, 0x272746c2, 2896996118.0, 3503322661.0, 0x50501386, 3705468758.0, 2223250005.0, 0.0, 0x7a7a25be, 0x1313ef91);
    /**
     * M-Table
     *
     * @var array
     * @access private
     */
    var $m1 = array(2849585465.0, 0x67901717, 3010567324.0, 0.0, 0x4050707, 4254618194.0, 2741338240.0, 0x76dfe4e4, 2584233285.0, 2449623883.0, 0.0, 0x78665a5a, 0.0, 3719326314.0, 3518980963.0, 0x38362a2a, 0xd54e6e6, 3326287904.0, 0x3562cccc, 0.0, 0x181e1212, 0.0, 0.0, 0x6c774141, 0x43bd2828, 0x7532bcbc, 0x37d47b7b, 0x269b8888, 4201647373.0, 0x13f94444, 2494692347.0, 0x485a7e7e, 4068082435.0, 0.0, 2336732854.0, 0x303c2424, 0.0, 0x54416b6b, 3741769181.0, 0x23c56060, 0x1945fdfd, 0x5ba33a3a, 0x3d68c2c2, 0x59158d8d, 0.0, 0.0, 0.0, 2182502231.0, 0x63951010, 0x15befef, 2202908856.0, 0x2e918686, 3652545901.0, 0x511f8383, 2605951658.0, 0x7c635d5d, 2788911208.0, 0.0, 2782277680.0, 0.0, 0x16a7acac, 0xc0f0909, 0.0, 0x6123a7a7, 3236991120.0, 0.0, 0x3a809d9d, 4120009820.0, 0x73810c0c, 0x2c273131, 0x2576d0d0, 0xbe75656, 3145437842.0, 0x4ee9cece, 2314273025.0, 0x6b9f1e1e, 0x53a93434, 0x6ac4f1f1, 3029976003.0, 4053228379.0, 0.0, 0.0, 3184009762.0, 0x450e9898, 0.0, 4106859443.0, 3056563316.0, 0x66cbf8f8, 3439303065.0, 0.0, 0x3ed5858, 0x56f7dcdc, 0.0, 0x1c1b1515, 0x1eada2a2, 3607942099.0, 0.0, 3273509064.0, 0.0, 3049401388.0, 0.0, 3474112961.0, 0.0, 3122691453.0, 0.0, 0x77840b0b, 0x396dc5c5, 2942994825.0, 0x33d17c7c, 3382800753.0, 0x62ceffff, 0x7137bbbb, 2180714255.0, 0x793db5b5, 0x951e1e1, 0.0, 0x242d3f3f, 3450107510.0, 4187837781.0, 0.0, 0.0, 0.0, 3117229349.0, 0x4d049696, 0x44557777, 0x80a0e0e, 2249412688.0, 0.0, 2714974007.0, 0x1d40fafa, 2855559521.0, 0.0, 0x6b3b0b0, 0x706c5454, 2989126515.0, 3528604475.0, 0x410b9f9f, 0x7b8b0202, 2693322968.0, 0x114ff3f3, 0x3167cbcb, 3259377447.0, 0x27c06767, 2427780348.0, 0x20283838, 4135519236.0, 0x60784848, 0.0, 2517060684.0, 0x5c4b6565, 2982619947.0, 0.0, 0.0, 2629563893.0, 0x52f2dbdb, 0x1bf34a4a, 0x5fa63d3d, 2472125604.0, 0xabcb9b9, 0.0, 0.0, 0.0, 0x49019191, 0.0, 0x2d7cdede, 0x4fb22121, 2403512753.0, 0x3bdb7272, 0x47b82f2f, 2269691839.0, 0x6d2caeae, 0x46e3c0c0, 3596041276.0, 0x3e859a9a, 0x6929a9a9, 0x647d4f4f, 0x2a948181, 0.0, 3407333062.0, 0x2fca6969, 4240686525.0, 2539430819.0, 0x55ee8e8, 0x7ad0eded, 2894582225.0, 0x7f8e0505, 3585762404.0, 0x1aa8a5a5, 0x4bb72626, 0xeb9bebe, 2808121223.0, 0x5af8d5d5, 0x28223636, 0x14111b1b, 0x3fde7575, 0x2979d9d9, 0.0, 0x3c332d2d, 0x4c5f7979, 0x2b6b7b7, 3096890058.0, 3663213877.0, 2963064004.0, 0x17fc4343, 0x551a8484, 0x1ff64d4d, 2317113689.0, 0x7d38b2b2, 0x57ac3333, 3340292047.0, 2381579782.0, 0x74695353, 3077872539.0, 3304429463.0, 2673257901.0, 0x72dae3e3, 0x7ed5eaea, 0x154af4f4, 0x229e8f8f, 0x12a2abab, 0x584e6262, 0x7e85f5f, 0.0, 0x34392323, 0x6ec1f6f6, 0x50446c6c, 0.0, 0x68724646, 0x6526a0a0, 3163803085.0, 3674462938.0, 4173773498.0, 0.0, 2827146966.0, 0x2bcf6e6e, 0x40507070, 0.0, 0.0, 0x328a9393, 2760761311.0, 3393988905.0, 0x10141c1c, 0x2173d7d7, 4039947444.0, 3540636884.0, 0x5d108a8a, 0xfe25151, 0x0, 0x6f9a1919, 0.0, 0x368f9494, 0x42e6c7c7, 0x4aecc9c9, 0x5efdd2d2, 3249241983.0, 0.0);
    /**
     * M-Table
     *
     * @var array
     * @access private
     */
    var $m2 = array(3161832498.0, 0.0, 0x20c62043, 3019158473.0, 3671841283.0, 0x27b028b, 0.0, 0.0, 0.0, 3570652169.0, 0x18e6186b, 0x1e6b1e9f, 0.0, 2994582072.0, 0.0, 0x264b26b7, 0x3cd63c57, 2469565322.0, 0.0, 0x52fd5298, 0x7b377bd4, 3144792887.0, 0x5bf15b97, 0x47e14783, 0x2430243c, 0x510f51e2, 3136862918.0, 0x4a1b4af3, 3213344584.0, 0xdfa0d70, 2953228467.0, 0x753f75de, 0.0, 0x7dba7d20, 0x66ae6631, 0x3a5b3aa3, 0x598a591c, 0x0, 3451702675.0, 0x1a9d1ae0, 0.0, 0x7fc17fab, 0x2bb12bc7, 0.0, 0.0, 2321386000.0, 0x3bd23b52, 0x64d564ba, 3634419848.0, 0.0, 0x5f075fe8, 0x1b141b11, 0x2cb52cc2, 4237360308.0, 0x312c3127, 2158198885.0, 0x73b2732a, 0xc730c81, 0x794c795f, 0x6b546b41, 0x4b924b02, 0x53745369, 2486604943.0, 2203157279.0, 0x2a382a36, 3299919004.0, 0x22bd22c8, 3579500024.0, 3187457475.0, 0x48604878, 0.0, 0x4c964c07, 0x416c4177, 0.0, 0.0, 0x1c101c14, 0x5d7c5d63, 0x36283622, 0x672767c0, 0.0, 0x441344f9, 0x149514ea, 4120704443.0, 3485978392.0, 0x3f243f2d, 0.0, 0x723b72db, 0x5470546c, 0x29ca294c, 0.0, 0x88508fe, 3335243287.0, 4078039887.0, 0.0, 2761139289.0, 3401108118.0, 0x68a6683b, 3095640141.0, 0x38203828, 0.0, 2912922966.0, 0xb770b84, 3368273949.0, 2580322815.0, 0x580358ed, 0x196f199a, 0xe080e0a, 0.0, 0x70407050, 0.0, 0x6e2b6ecf, 0x1fe21f6e, 3044652349.0, 0x90c090f, 0x61aa6134, 0x57825716, 2671877899.0, 2637864320.0, 0x11ea1164, 0x25b925cd, 0.0, 0x459a4508, 3752124301.0, 2744623964.0, 0.0, 0x35da3558, 0.0, 0x431743fc, 4167497931.0, 4220844977.0, 0x37a137d3, 4196268608.0, 3258827368.0, 3035673804.0, 0x32de325d, 2629016689.0, 0x560b56e7, 0.0, 2275903328.0, 0x151c151b, 0.0, 0x63d163bf, 0x345334a9, 0.0, 2978984258.0, 0x7c337cd1, 2284226715.0, 0x3d5f3da6, 0.0, 0.0, 2167046548.0, 2437517569.0, 0xf810ffb, 0.0, 0x16ee1661, 3609319283.0, 2546243573.0, 2769986984.0, 0.0, 0x6dd96db5, 0x78c578ae, 3308897645.0, 0x1d991de5, 0x76cd76a4, 0x3ead3edc, 3409038183.0, 3062609479.0, 0.0, 0x1218121e, 0x602360c5, 0x6add6ab0, 0x4d1f4df6, 0.0, 0.0, 0x55f9559d, 0x7e487e5a, 0x214f21b2, 0x3f2037a, 2691014694.0, 0x5e8e5e19, 0x5a785a66, 0x655c654b, 0x6258624e, 4246338885.0, 0x68d06f4, 0x40e54086, 0.0, 0x335733ac, 0x17671790, 0x57f058e, 0.0, 0x4f644f7d, 2309982570.0, 0x10631095, 0x74b6742f, 0xafe0a75, 0x5cf55c92, 2612501364.0, 0x2d3c2d33, 0x30a530d6, 0x2ece2e49, 0x49e94989, 0x46684672, 0x77447755, 0.0, 2521667076.0, 0x284328bd, 2842274089.0, 3643398521.0, 0.0, 3517763975.0, 4095079498.0, 2371456277.0, 3601389186.0, 3104487868.0, 0x429e420d, 0.0, 0x2f472fb8, 3722435846.0, 0x23342339, 3426077794.0, 4050317764.0, 3251618066.0, 0.0, 0.0, 0x71c971a1, 2428539120.0, 2862328403.0, 0x18901f1, 0.0, 0x4eed4e8c, 0.0, 2870127522.0, 0x6fa26f3e, 0.0, 3679640562.0, 2461766267.0, 3070408630.0, 0x692f69ca, 0x39a939d9, 3554136844.0, 2808194851.0, 0.0, 3283403673.0, 0x6c506c44, 0x7040705, 0x4f6047f, 0x27c22746, 2887167143.0, 3492139126.0, 0x50865013, 3696680183.0, 2220196890.0, 0.0, 0x7abe7a25, 0x139113ef);
    /**
     * M-Table
     *
     * @var array
     * @access private
     */
    var $m3 = array(3644434905.0, 2417452944.0, 0x719cb371, 0.0, 0x5070405, 2555575704.0, 0x6580a365, 0.0, 0x8459a08, 0x24b9202, 0.0, 0x665a7866, 0.0, 2959793584.0, 3210990015.0, 0x362a3836, 0x54e60d54, 0x4320c643, 0x62cc3562, 0.0, 0x1e12181e, 0x24ebf724, 0.0, 0x77416c77, 3173532605.0, 0x32bc7532, 3564845012.0, 2609391259.0, 0x700dfa70, 4181988345.0, 2986054833.0, 0x5a7e485a, 0x7a03f27a, 0.0, 0x47b68b47, 0x3c24303c, 0.0, 0x416b5441, 0x6dddf06, 3311412165.0, 0x45fd1945, 2738510755.0, 0x68c23d68, 0x158d5915, 0x21ecf321, 0x3166ae31, 0x3e6fa23e, 0x16578216, 2500879253.0, 0x5bef015b, 0x4db8834d, 0.0, 3043875253.0, 0x1f83511f, 0x53aa9b53, 0x635d7c63, 0x3b68a63b, 0x3ffeeb3f, 3593512406.0, 0x257abe25, 2813073063.0, 0xf090c0f, 0x35f0e335, 0x23a76123, 4036018416.0, 0.0, 2157787776.0, 2455565714.0, 2165076865.0, 0x27312c27, 0x76d02576, 0.0, 0x7b92bb7b, 0.0, 4043409905.0, 0.0, 2838778793.0, 3304155844.0, 2579739801.0, 2539385239.0, 0.0, 0x6b18e66b, 3357720008.0, 0xe98450e, 0x6e1fe26e, 3384014025.0, 0x2f74b62f, 3422054091.0, 4288269567.0, 0.0, 0.0, 4158412535.0, 0.0, 0x1b151c1b, 0.0, 0xcd3d70c, 0x2be2fb2b, 0x1dc8c31d, 0x195e8e19, 3257710018.0, 0.0, 0x12c1cf12, 0x7e95bf7e, 0x207dba20, 0x6411ea64, 2215344004.0, 0x6dc5396d, 0x6a89af6a, 3514577873.0, 2708588961.0, 0.0, 0x37bb7137, 4212097531.0, 0x3db5793d, 0x51e10951, 0.0, 0x2d3f242d, 2759249316.0, 2639657373.0, 0.0, 0.0, 0.0, 3441801677.0, 0x4964d04, 0x55774455, 0xa0e080a, 0x13508613, 0x30f7e730, 3543638483.0, 0x40fa1d40, 0x3461aa34, 0.0, 3014657715.0, 0x6c54706c, 0x2a73b22a, 0x523bd252, 0xb9f410b, 2332195723.0, 2295898248.0, 0x4ff3114f, 0x67cb3167, 0x4627c246, 3227985856.0, 3036450996.0, 0x28382028, 0x7f04f67f, 0x78486078, 0x2ee5ff2e, 0x74c9607, 0x4b655c4b, 3341529543.0, 0x6f8eab6f, 0xd429e0d, 3153435835.0, 4074459890.0, 4081720307.0, 2789040038.0, 0x59a49359, 3166243516.0, 0x3af9ef3a, 0.0, 0.0, 0x1914901, 0x6116ee61, 0x7cde2d7c, 2988527538.0, 0x42b18f42, 3681696731.0, 3090106296.0, 0x48bf8748, 0x2cae6d2c, 0.0, 0x573cd657, 0.0, 0x29a96929, 0x7d4f647d, 2491493012.0, 0x492ece49, 0x17c6cb17, 3395891146.0, 3284008131.0, 0x5ca3975c, 0x5ee8055e, 0.0, 2278665351.0, 0.0, 3127170490.0, 2829392552.0, 3072740279.0, 0.0, 0x6087a760, 4174732024.0, 0x22362822, 0x111b1411, 0.0, 0x79d92979, 0.0, 0x332d3c33, 0x5f794c5f, 3065447094.0, 2529867926.0, 0x5835da58, 2630135964.0, 4232255484.0, 0x1a84551a, 4132249590.0, 0x1c598a1c, 0x38b27d38, 2889045932.0, 0x18cfc718, 4094070260.0, 0x69537469, 0x749bb774, 4120364277.0, 0x56ad9f56, 0.0, 0.0, 0x4af4154a, 0.0, 2729120418.0, 0x4e62584e, 0.0, 0.0, 0x39233439, 0.0, 0x446c5044, 0x5d32de5d, 0x72466872, 0x26a06526, 2479733907.0, 0x3dadb03, 3334142150.0, 0.0, 2195105922.0, 0.0, 0x50704050, 0.0, 0x750afe75, 2324902538.0, 2380244109.0, 0x4c29ca4c, 0x141c1014, 0x73d72173, 3434410188.0, 0x9d4d309, 0x108a5d10, 0.0, 0x0, 2585358234.0, 0.0, 2408855183.0, 0.0, 0.0, 0.0, 2877276587.0, 0.0);
    /**
     * The Key Schedule Array
     *
     * @var array
     * @access private
     */
    var $K = array();
    /**
     * The Key depended S-Table 0
     *
     * @var array
     * @access private
     */
    var $S0 = array();
    /**
     * The Key depended S-Table 1
     *
     * @var array
     * @access private
     */
    var $S1 = array();
    /**
     * The Key depended S-Table 2
     *
     * @var array
     * @access private
     */
    var $S2 = array();
    /**
     * The Key depended S-Table 3
     *
     * @var array
     * @access private
     */
    var $S3 = array();
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
     * @see Crypt_Twofish::setKeyLength()
     * @var int
     * @access private
     */
    var $key_length = 16;
    /**
     * Sets the key length.
     *
     * Valid key lengths are 128, 192 or 256 bits
     *
     * @access public
     * @param int $length
     */
    function setKeyLength($length)
    {
        switch (true) {
            case $length <= 128:
                $this->key_length = 16;
                break;
            case $length <= 192:
                $this->key_length = 24;
                break;
            default:
                $this->key_length = 32;
        }
        parent::setKeyLength($length);
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
        /* Key expanding and generating the key-depended s-boxes */
        $le_longs = unpack('V*', $this->key);
        $key = unpack('C*', $this->key);
        $m0 = $this->m0;
        $m1 = $this->m1;
        $m2 = $this->m2;
        $m3 = $this->m3;
        $q0 = $this->q0;
        $q1 = $this->q1;
        $K = $S0 = $S1 = $S2 = $S3 = array();
        switch (strlen($this->key)) {
            case 16:
                list($s7, $s6, $s5, $s4) = $this->_mdsrem($le_longs[1], $le_longs[2]);
                list($s3, $s2, $s1, $s0) = $this->_mdsrem($le_longs[3], $le_longs[4]);
                for ($i = 0, $j = 1; $i < 40; $i += 2, $j += 2) {
                    $A = $m0[$q0[$q0[$i] ^ $key[9]] ^ $key[1]] ^ $m1[$q0[$q1[$i] ^ $key[10]] ^ $key[2]] ^ $m2[$q1[$q0[$i] ^ $key[11]] ^ $key[3]] ^ $m3[$q1[$q1[$i] ^ $key[12]] ^ $key[4]];
                    $B = $m0[$q0[$q0[$j] ^ $key[13]] ^ $key[5]] ^ $m1[$q0[$q1[$j] ^ $key[14]] ^ $key[6]] ^ $m2[$q1[$q0[$j] ^ $key[15]] ^ $key[7]] ^ $m3[$q1[$q1[$j] ^ $key[16]] ^ $key[8]];
                    $B = $B << 8 | $B >> 24 & 0xff;
                    $A = $this->safe_intval($A + $B);
                    $K[] = $A;
                    $A = $this->safe_intval($A + $B);
                    $K[] = $A << 9 | $A >> 23 & 0x1ff;
                }
                for ($i = 0; $i < 256; ++$i) {
                    $S0[$i] = $m0[$q0[$q0[$i] ^ $s4] ^ $s0];
                    $S1[$i] = $m1[$q0[$q1[$i] ^ $s5] ^ $s1];
                    $S2[$i] = $m2[$q1[$q0[$i] ^ $s6] ^ $s2];
                    $S3[$i] = $m3[$q1[$q1[$i] ^ $s7] ^ $s3];
                }
                break;
            case 24:
                list($sb, $sa, $s9, $s8) = $this->_mdsrem($le_longs[1], $le_longs[2]);
                list($s7, $s6, $s5, $s4) = $this->_mdsrem($le_longs[3], $le_longs[4]);
                list($s3, $s2, $s1, $s0) = $this->_mdsrem($le_longs[5], $le_longs[6]);
                for ($i = 0, $j = 1; $i < 40; $i += 2, $j += 2) {
                    $A = $m0[$q0[$q0[$q1[$i] ^ $key[17]] ^ $key[9]] ^ $key[1]] ^ $m1[$q0[$q1[$q1[$i] ^ $key[18]] ^ $key[10]] ^ $key[2]] ^ $m2[$q1[$q0[$q0[$i] ^ $key[19]] ^ $key[11]] ^ $key[3]] ^ $m3[$q1[$q1[$q0[$i] ^ $key[20]] ^ $key[12]] ^ $key[4]];
                    $B = $m0[$q0[$q0[$q1[$j] ^ $key[21]] ^ $key[13]] ^ $key[5]] ^ $m1[$q0[$q1[$q1[$j] ^ $key[22]] ^ $key[14]] ^ $key[6]] ^ $m2[$q1[$q0[$q0[$j] ^ $key[23]] ^ $key[15]] ^ $key[7]] ^ $m3[$q1[$q1[$q0[$j] ^ $key[24]] ^ $key[16]] ^ $key[8]];
                    $B = $B << 8 | $B >> 24 & 0xff;
                    $A = $this->safe_intval($A + $B);
                    $K[] = $A;
                    $A = $this->safe_intval($A + $B);
                    $K[] = $A << 9 | $A >> 23 & 0x1ff;
                }
                for ($i = 0; $i < 256; ++$i) {
                    $S0[$i] = $m0[$q0[$q0[$q1[$i] ^ $s8] ^ $s4] ^ $s0];
                    $S1[$i] = $m1[$q0[$q1[$q1[$i] ^ $s9] ^ $s5] ^ $s1];
                    $S2[$i] = $m2[$q1[$q0[$q0[$i] ^ $sa] ^ $s6] ^ $s2];
                    $S3[$i] = $m3[$q1[$q1[$q0[$i] ^ $sb] ^ $s7] ^ $s3];
                }
                break;
            default:
                // 32
                list($sf, $se, $sd, $sc) = $this->_mdsrem($le_longs[1], $le_longs[2]);
                list($sb, $sa, $s9, $s8) = $this->_mdsrem($le_longs[3], $le_longs[4]);
                list($s7, $s6, $s5, $s4) = $this->_mdsrem($le_longs[5], $le_longs[6]);
                list($s3, $s2, $s1, $s0) = $this->_mdsrem($le_longs[7], $le_longs[8]);
                for ($i = 0, $j = 1; $i < 40; $i += 2, $j += 2) {
                    $A = $m0[$q0[$q0[$q1[$q1[$i] ^ $key[25]] ^ $key[17]] ^ $key[9]] ^ $key[1]] ^ $m1[$q0[$q1[$q1[$q0[$i] ^ $key[26]] ^ $key[18]] ^ $key[10]] ^ $key[2]] ^ $m2[$q1[$q0[$q0[$q0[$i] ^ $key[27]] ^ $key[19]] ^ $key[11]] ^ $key[3]] ^ $m3[$q1[$q1[$q0[$q1[$i] ^ $key[28]] ^ $key[20]] ^ $key[12]] ^ $key[4]];
                    $B = $m0[$q0[$q0[$q1[$q1[$j] ^ $key[29]] ^ $key[21]] ^ $key[13]] ^ $key[5]] ^ $m1[$q0[$q1[$q1[$q0[$j] ^ $key[30]] ^ $key[22]] ^ $key[14]] ^ $key[6]] ^ $m2[$q1[$q0[$q0[$q0[$j] ^ $key[31]] ^ $key[23]] ^ $key[15]] ^ $key[7]] ^ $m3[$q1[$q1[$q0[$q1[$j] ^ $key[32]] ^ $key[24]] ^ $key[16]] ^ $key[8]];
                    $B = $B << 8 | $B >> 24 & 0xff;
                    $A = $this->safe_intval($A + $B);
                    $K[] = $A;
                    $A = $this->safe_intval($A + $B);
                    $K[] = $A << 9 | $A >> 23 & 0x1ff;
                }
                for ($i = 0; $i < 256; ++$i) {
                    $S0[$i] = $m0[$q0[$q0[$q1[$q1[$i] ^ $sc] ^ $s8] ^ $s4] ^ $s0];
                    $S1[$i] = $m1[$q0[$q1[$q1[$q0[$i] ^ $sd] ^ $s9] ^ $s5] ^ $s1];
                    $S2[$i] = $m2[$q1[$q0[$q0[$q0[$i] ^ $se] ^ $sa] ^ $s6] ^ $s2];
                    $S3[$i] = $m3[$q1[$q1[$q0[$q1[$i] ^ $sf] ^ $sb] ^ $s7] ^ $s3];
                }
        }
        $this->K = $K;
        $this->S0 = $S0;
        $this->S1 = $S1;
        $this->S2 = $S2;
        $this->S3 = $S3;
    }
    /**
     * _mdsrem function using by the twofish cipher algorithm
     *
     * @access private
     * @param string $A
     * @param string $B
     * @return array
     */
    function _mdsrem($A, $B)
    {
        // No gain by unrolling this loop.
        for ($i = 0; $i < 8; ++$i) {
            // Get most significant coefficient.
            $t = 0xff & $B >> 24;
            // Shift the others up.
            $B = $B << 8 | 0xff & $A >> 24;
            $A <<= 8;
            $u = $t << 1;
            // Subtract the modular polynomial on overflow.
            if ($t & 0x80) {
                $u ^= 0x14d;
            }
            // Remove t * (a * x^2 + 1).
            $B ^= $t ^ $u << 16;
            // Form u = a*t + t/a = t*(a + 1/a).
            $u ^= 0x7fffffff & $t >> 1;
            // Add the modular polynomial on underflow.
            if ($t & 0x1) {
                $u ^= 0xa6;
            }
            // Remove t * (a + 1/a) * (x^3 + x).
            $B ^= $u << 24 | $u << 8;
        }
        return array(0xff & $B >> 24, 0xff & $B >> 16, 0xff & $B >> 8, 0xff & $B);
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
        $S0 = $this->S0;
        $S1 = $this->S1;
        $S2 = $this->S2;
        $S3 = $this->S3;
        $K = $this->K;
        $in = unpack("V4", $in);
        $R0 = $K[0] ^ $in[1];
        $R1 = $K[1] ^ $in[2];
        $R2 = $K[2] ^ $in[3];
        $R3 = $K[3] ^ $in[4];
        $ki = 7;
        while ($ki < 39) {
            $t0 = $S0[$R0 & 0xff] ^ $S1[$R0 >> 8 & 0xff] ^ $S2[$R0 >> 16 & 0xff] ^ $S3[$R0 >> 24 & 0xff];
            $t1 = $S0[$R1 >> 24 & 0xff] ^ $S1[$R1 & 0xff] ^ $S2[$R1 >> 8 & 0xff] ^ $S3[$R1 >> 16 & 0xff];
            $R2 ^= $this->safe_intval($t0 + $t1 + $K[++$ki]);
            $R2 = $R2 >> 1 & 0x7fffffff | $R2 << 31;
            $R3 = ($R3 >> 31 & 1 | $R3 << 1) ^ $this->safe_intval($t0 + ($t1 << 1) + $K[++$ki]);
            $t0 = $S0[$R2 & 0xff] ^ $S1[$R2 >> 8 & 0xff] ^ $S2[$R2 >> 16 & 0xff] ^ $S3[$R2 >> 24 & 0xff];
            $t1 = $S0[$R3 >> 24 & 0xff] ^ $S1[$R3 & 0xff] ^ $S2[$R3 >> 8 & 0xff] ^ $S3[$R3 >> 16 & 0xff];
            $R0 ^= $this->safe_intval($t0 + $t1 + $K[++$ki]);
            $R0 = $R0 >> 1 & 0x7fffffff | $R0 << 31;
            $R1 = ($R1 >> 31 & 1 | $R1 << 1) ^ $this->safe_intval($t0 + ($t1 << 1) + $K[++$ki]);
        }
        // @codingStandardsIgnoreStart
        return pack("V4", $K[4] ^ $R2, $K[5] ^ $R3, $K[6] ^ $R0, $K[7] ^ $R1);
        // @codingStandardsIgnoreEnd
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
        $S0 = $this->S0;
        $S1 = $this->S1;
        $S2 = $this->S2;
        $S3 = $this->S3;
        $K = $this->K;
        $in = unpack("V4", $in);
        $R0 = $K[4] ^ $in[1];
        $R1 = $K[5] ^ $in[2];
        $R2 = $K[6] ^ $in[3];
        $R3 = $K[7] ^ $in[4];
        $ki = 40;
        while ($ki > 8) {
            $t0 = $S0[$R0 & 0xff] ^ $S1[$R0 >> 8 & 0xff] ^ $S2[$R0 >> 16 & 0xff] ^ $S3[$R0 >> 24 & 0xff];
            $t1 = $S0[$R1 >> 24 & 0xff] ^ $S1[$R1 & 0xff] ^ $S2[$R1 >> 8 & 0xff] ^ $S3[$R1 >> 16 & 0xff];
            $R3 ^= $this->safe_intval($t0 + ($t1 << 1) + $K[--$ki]);
            $R3 = $R3 >> 1 & 0x7fffffff | $R3 << 31;
            $R2 = ($R2 >> 31 & 0x1 | $R2 << 1) ^ $this->safe_intval($t0 + $t1 + $K[--$ki]);
            $t0 = $S0[$R2 & 0xff] ^ $S1[$R2 >> 8 & 0xff] ^ $S2[$R2 >> 16 & 0xff] ^ $S3[$R2 >> 24 & 0xff];
            $t1 = $S0[$R3 >> 24 & 0xff] ^ $S1[$R3 & 0xff] ^ $S2[$R3 >> 8 & 0xff] ^ $S3[$R3 >> 16 & 0xff];
            $R1 ^= $this->safe_intval($t0 + ($t1 << 1) + $K[--$ki]);
            $R1 = $R1 >> 1 & 0x7fffffff | $R1 << 31;
            $R0 = ($R0 >> 31 & 0x1 | $R0 << 1) ^ $this->safe_intval($t0 + $t1 + $K[--$ki]);
        }
        // @codingStandardsIgnoreStart
        return pack("V4", $K[0] ^ $R2, $K[1] ^ $R3, $K[2] ^ $R0, $K[3] ^ $R1);
        // @codingStandardsIgnoreEnd
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
        // Max. 10 Ultra-Hi-optimized inline-crypt functions. After that, we'll (still) create very fast code, but not the ultimate fast one.
        // (Currently, for Crypt_Twofish, one generated $lambda_function cost on php5.5@32bit ~140kb unfreeable mem and ~240kb on php5.5@64bit)
        $gen_hi_opt_code = (bool) (count($lambda_functions) < 10);
        // Generation of a unique hash for our generated code
        $code_hash = "Crypt_Twofish, {$this->mode}";
        if ($gen_hi_opt_code) {
            $code_hash = str_pad($code_hash, 32) . $this->_hashInlineCryptFunction($this->key);
        }
        $safeint = $this->safe_intval_inline();
        if (!isset($lambda_functions[$code_hash])) {
            switch (true) {
                case $gen_hi_opt_code:
                    $K = $this->K;
                    $init_crypt = '
                        static $S0, $S1, $S2, $S3;
                        if (!$S0) {
                            for ($i = 0; $i < 256; ++$i) {
                                $S0[] = (int)$self->S0[$i];
                                $S1[] = (int)$self->S1[$i];
                                $S2[] = (int)$self->S2[$i];
                                $S3[] = (int)$self->S3[$i];
                            }
                        }
                    ';
                    break;
                default:
                    $K = array();
                    for ($i = 0; $i < 40; ++$i) {
                        $K[] = '$K_' . $i;
                    }
                    $init_crypt = '
                        $S0 = $self->S0;
                        $S1 = $self->S1;
                        $S2 = $self->S2;
                        $S3 = $self->S3;
                        list(' . implode(',', $K) . ') = $self->K;
                    ';
            }
            // Generating encrypt code:
            $encrypt_block = '
                $in = unpack("V4", $in);
                $R0 = ' . $K[0] . ' ^ $in[1];
                $R1 = ' . $K[1] . ' ^ $in[2];
                $R2 = ' . $K[2] . ' ^ $in[3];
                $R3 = ' . $K[3] . ' ^ $in[4];
            ';
            for ($ki = 7, $i = 0; $i < 8; ++$i) {
                $encrypt_block .= '
                    $t0 = $S0[ $R0        & 0xff] ^
                          $S1[($R0 >>  8) & 0xff] ^
                          $S2[($R0 >> 16) & 0xff] ^
                          $S3[($R0 >> 24) & 0xff];
                    $t1 = $S0[($R1 >> 24) & 0xff] ^
                          $S1[ $R1        & 0xff] ^
                          $S2[($R1 >>  8) & 0xff] ^
                          $S3[($R1 >> 16) & 0xff];
                    $R2^= ' . sprintf($safeint, '$t0 + $t1 + ' . $K[++$ki]) . ';
                    $R2 = ($R2 >> 1 & 0x7fffffff) | ($R2 << 31);
                    $R3 = ((($R3 >> 31) & 1) | ($R3 << 1)) ^ ' . sprintf($safeint, '($t0 + ($t1 << 1) + ' . $K[++$ki] . ')') . ';

                    $t0 = $S0[ $R2        & 0xff] ^
                          $S1[($R2 >>  8) & 0xff] ^
                          $S2[($R2 >> 16) & 0xff] ^
                          $S3[($R2 >> 24) & 0xff];
                    $t1 = $S0[($R3 >> 24) & 0xff] ^
                          $S1[ $R3        & 0xff] ^
                          $S2[($R3 >>  8) & 0xff] ^
                          $S3[($R3 >> 16) & 0xff];
                    $R0^= ' . sprintf($safeint, '($t0 + $t1 + ' . $K[++$ki] . ')') . ';
                    $R0 = ($R0 >> 1 & 0x7fffffff) | ($R0 << 31);
                    $R1 = ((($R1 >> 31) & 1) | ($R1 << 1)) ^ ' . sprintf($safeint, '($t0 + ($t1 << 1) + ' . $K[++$ki] . ')') . ';
                ';
            }
            $encrypt_block .= '
                $in = pack("V4", ' . $K[4] . ' ^ $R2,
                                 ' . $K[5] . ' ^ $R3,
                                 ' . $K[6] . ' ^ $R0,
                                 ' . $K[7] . ' ^ $R1);
            ';
            // Generating decrypt code:
            $decrypt_block = '
                $in = unpack("V4", $in);
                $R0 = ' . $K[4] . ' ^ $in[1];
                $R1 = ' . $K[5] . ' ^ $in[2];
                $R2 = ' . $K[6] . ' ^ $in[3];
                $R3 = ' . $K[7] . ' ^ $in[4];
            ';
            for ($ki = 40, $i = 0; $i < 8; ++$i) {
                $decrypt_block .= '
                    $t0 = $S0[$R0       & 0xff] ^
                          $S1[$R0 >>  8 & 0xff] ^
                          $S2[$R0 >> 16 & 0xff] ^
                          $S3[$R0 >> 24 & 0xff];
                    $t1 = $S0[$R1 >> 24 & 0xff] ^
                          $S1[$R1       & 0xff] ^
                          $S2[$R1 >>  8 & 0xff] ^
                          $S3[$R1 >> 16 & 0xff];
                    $R3^= ' . sprintf($safeint, '$t0 + ($t1 << 1) + ' . $K[--$ki]) . ';
                    $R3 = $R3 >> 1 & 0x7fffffff | $R3 << 31;
                    $R2 = ($R2 >> 31 & 0x1 | $R2 << 1) ^ ' . sprintf($safeint, '($t0 + $t1 + ' . $K[--$ki] . ')') . ';

                    $t0 = $S0[$R2       & 0xff] ^
                          $S1[$R2 >>  8 & 0xff] ^
                          $S2[$R2 >> 16 & 0xff] ^
                          $S3[$R2 >> 24 & 0xff];
                    $t1 = $S0[$R3 >> 24 & 0xff] ^
                          $S1[$R3       & 0xff] ^
                          $S2[$R3 >>  8 & 0xff] ^
                          $S3[$R3 >> 16 & 0xff];
                    $R1^= ' . sprintf($safeint, '$t0 + ($t1 << 1) + ' . $K[--$ki]) . ';
                    $R1 = $R1 >> 1 & 0x7fffffff | $R1 << 31;
                    $R0 = ($R0 >> 31 & 0x1 | $R0 << 1) ^ ' . sprintf($safeint, '($t0 + $t1 + ' . $K[--$ki] . ')') . ';
                ';
            }
            $decrypt_block .= '
                $in = pack("V4", ' . $K[0] . ' ^ $R2,
                                 ' . $K[1] . ' ^ $R3,
                                 ' . $K[2] . ' ^ $R0,
                                 ' . $K[3] . ' ^ $R1);
            ';
            $lambda_functions[$code_hash] = $this->_createInlineCryptFunction(array('init_crypt' => $init_crypt, 'init_encrypt' => '', 'init_decrypt' => '', 'encrypt_block' => $encrypt_block, 'decrypt_block' => $decrypt_block));
        }
        $this->inline_crypt = $lambda_functions[$code_hash];
    }
}

?>